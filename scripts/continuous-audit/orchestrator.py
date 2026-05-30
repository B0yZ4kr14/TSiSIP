#!/usr/bin/env python3
"""
TSiSIP Continuous Audit Orchestrator
Runs automated scans and safe auto-remediations.
Mode --single-cycle: run one audit cycle and exit (for systemd timer).
Mode default: run MAX_CYCLES in a loop (for manual/foreground execution).
"""
import argparse
import json
import os
import re
import subprocess
import sys
import time
import glob as glob_mod
from datetime import datetime, timezone
from pathlib import Path

PROJECT_ROOT = Path("/opt/tsisip")
LOG_DIR = PROJECT_ROOT / "logs" / "continuous-audit"
STATE_FILE = LOG_DIR / "state.json"
MAX_CYCLES = 48
SLEEP_SECONDS = 15 * 60

AUTO_REMEDIATIONS = [
    {
        "id": "AR-001",
        "name": "bash pipefail",
        "glob": "docker/**/entrypoint.sh",
        "check": r"^set -eu$",
        "replace": (r"^set -eu$", "set -euo pipefail"),
        "safe": True,
    },
    {
        "id": "AR-003",
        "name": "certbot_exporter memory",
        "glob": "docker-compose*.yml",
        "check": r"memory: 64M",
        "replace": (r"memory: 64M", "memory: 128M"),
        "safe": True,
    },
    {
        "id": "AR-004",
        "name": "tailscale_cert registry prefix",
        "glob": "docker-compose*.yml",
        "check": r"image: tsisip/tailscale_cert:",
        "replace": (r"image: tsisip/tailscale_cert:", "image: ghcr.io/b0yz4kr14/tsisip/tailscale_cert:"),
        "safe": True,
    },
    {
        "id": "AR-005",
        "name": "ANOMALY_API_KEY fallback",
        "glob": "docker-compose*.yml",
        "check": r"ANOMALY_API_KEY: \$\{ANOMALY_API_KEY:-change-me-in-production\}",
        "replace": (r"ANOMALY_API_KEY: \$\{ANOMALY_API_KEY:-change-me-in-production\}", "ANOMALY_API_KEY: ${ANOMALY_API_KEY:?must be set}"),
        "safe": True,
    },
    {
        "id": "AR-006",
        "name": "RTPengine HTTP 0.0.0.0",
        "glob": "docker-compose*.yml",
        "check": r"--listen-http=0\.0\.0\.0:",
        "replace": (r"--listen-http=0\.0\.0\.0:", "--listen-http=${RTPENGINE_INTERNAL_IP}:"),
        "safe": True,
    },
    {
        "id": "AR-007",
        "name": "OpenSIPS TLS cert rw mount",
        "glob": "docker-compose*.yml",
        "check": r"tls_certs:/certs/live:rw",
        "replace": (r"tls_certs:/certs/live:rw", "tls_certs:/certs/live:ro"),
        "safe": True,
    },
]


def log(msg, fh):
    ts = datetime.now(timezone.utc).isoformat()
    line = f"[{ts}] {msg}"
    print(line)
    fh.write(line + "\n")
    fh.flush()


def run_cmd(cmd, cwd=PROJECT_ROOT, check=True):
    result = subprocess.run(cmd, cwd=cwd, capture_output=True, text=True, check=check)
    return result.stdout + result.stderr


def git_has_changes():
    result = run_cmd(["git", "status", "--short"], check=False)
    return bool(result.strip())


def git_commit(message):
    run_cmd(["git", "add", "-A"], check=False)
    run_cmd(["git", "commit", "-m", message], check=False)


def run_gitnexus_analyze(cycle_log):
    log(">> Running gitnexus analyze...", cycle_log)
    try:
        out = run_cmd(["npx", "gitnexus", "analyze"], check=False)
        log(f"gitnexus output: {out[:500]}", cycle_log)
        return {"status": "ran", "output": out[:1000]}
    except FileNotFoundError:
        log("gitnexus not available via npx", cycle_log)
        return {"status": "unavailable"}


def run_brownfield_checks(cycle_log):
    log(">> Running brownfield checks...", cycle_log)
    findings = []
    checks = [
        ("rg", ["db_mysql", "--type", "sh", "--type", "cfg", "--type", "yml"], "BF-001", "HIGH", "db_mysql reference"),
        ("rg", ['loadmodule "sanity.so"', "opensips/"], "BF-002", "HIGH", "sanity module"),
        ("rg", ["rtpengine_manage", "opensips/"], "BF-003", "HIGH", "rtpengine_manage"),
        ("rg", ["calculate_ha1.*=.*1", "opensips/"], "BF-004", "HIGH", "calculate_ha1=1"),
        ("rg", ['password_column.*=.*"password"', "opensips/"], "BF-005", "HIGH", "password_column=password"),
        ("rg", ['topology_hiding\\("U"\\)', "opensips/"], "BF-006", "MEDIUM", "topology_hiding(U)"),
        ("rg", [":latest", "docker-compose*.yml"], "BF-007", "MEDIUM", ":latest tag"),
    ]
    for tool, args, fid, sev, desc in checks:
        try:
            out = run_cmd([tool] + args, check=False)
            if out.strip() and ".venv" not in out and ".ansible-venv" not in out:
                findings.append({"id": fid, "severity": sev, "msg": f"{desc}: {out[:200]}", "auto_fix": False})
        except FileNotFoundError:
            pass
    log(f"Brownfield checks: {len(findings)} findings", cycle_log)
    for f in findings:
        log(f"  [{f['severity']}] {f['id']}: {f['msg']}", cycle_log)
    return findings


def run_memory_checks(cycle_log):
    log(">> Running memory checks...", cycle_log)
    findings = []
    vps_file = PROJECT_ROOT / "docker-compose.vps.yml"
    if vps_file.exists():
        content = vps_file.read_text()
        m_match = re.search(r'"-m",\s*"(\d+)"', content)
        M_match = re.search(r'"-M",\s*"(\d+)"', content)
        mem_match = re.search(r'memory:\s*(\d+)([MG])', content)
        if m_match and M_match and mem_match:
            m_val = int(m_match.group(1))
            M_val = int(M_match.group(1))
            mem_val = int(mem_match.group(1))
            mem_unit = mem_match.group(2)
            mem_mb = mem_val * 1024 if mem_unit == "G" else mem_val
            calculated = (8 + 1) * M_val + m_val
            if calculated > mem_mb:
                new_M = int((mem_mb - m_val) / 9)
                findings.append({
                    "id": "MEM-001", "severity": "CRITICAL",
                    "msg": f"OpenSIPS VPS over-allocated: {calculated}MB > {mem_mb}MB limit",
                    "auto_fix": True, "file": str(vps_file),
                    "pattern": f'"-M", "{M_val}"',
                    "replacement": f'"-M", "{new_M}"',
                })
    log(f"Memory checks: {len(findings)} findings", cycle_log)
    for f in findings:
        log(f"  [{f['severity']}] {f['id']}: {f['msg']}", cycle_log)
    return findings


def run_version_checks(cycle_log):
    log(">> Running version checks...", cycle_log)
    findings = []
    pyver_file = PROJECT_ROOT / ".python-version"
    if pyver_file.exists():
        version = pyver_file.read_text().strip()
        try:
            major, minor, patch = map(int, version.split("."))
            if major > 3 or (major == 3 and minor > 13):
                findings.append({
                    "id": "VER-001", "severity": "MEDIUM",
                    "msg": f".python-version {version} is not stable",
                    "auto_fix": True, "file": str(pyver_file),
                    "pattern": version, "replacement": "3.12.3",
                })
        except ValueError:
            pass
    pgbouncer_file = PROJECT_ROOT / "docker" / "pgbouncer" / "Dockerfile"
    if pgbouncer_file.exists():
        content = pgbouncer_file.read_text()
        if "pgbouncer/pgbouncer@sha256:" in content:
            prefix = content.split("@sha256:")[0]
            if ":1." not in prefix:
                findings.append({
                    "id": "VER-002", "severity": "MEDIUM",
                    "msg": "PgBouncer missing semantic version tag",
                    "auto_fix": False,
                })
    log(f"Version checks: {len(findings)} findings", cycle_log)
    for f in findings:
        log(f"  [{f['severity']}] {f['id']}: {f['msg']}", cycle_log)
    return findings


def apply_auto_remediations(findings, cycle_log):
    log(">> Applying auto-remediations...", cycle_log)
    applied = 0
    for f in findings:
        if not f.get("auto_fix"):
            continue
        filepath = Path(f["file"])
        if not filepath.exists():
            continue
        content = filepath.read_text()
        new_content = content.replace(f["pattern"], f["replacement"])
        if new_content != content:
            filepath.write_text(new_content)
            log(f"  Fixed {f['id']} in {filepath.name}", cycle_log)
            applied += 1
    for ar in AUTO_REMEDIATIONS:
        if not ar.get("safe") or ar["replace"] is None:
            continue
        pattern = str(PROJECT_ROOT / ar["glob"])
        for filepath in glob_mod.glob(pattern, recursive=True):
            if not os.path.isfile(filepath):
                continue
            p = Path(filepath)
            content = p.read_text()
            if re.search(ar["check"], content, re.MULTILINE):
                new_content = re.sub(ar["replace"][0], ar["replace"][1], content, flags=re.MULTILINE)
                if new_content != content:
                    p.write_text(new_content)
                    log(f"  Applied {ar['id']} ({ar['name']}) to {p.name}", cycle_log)
                    applied += 1
    log(f"Auto-remediations applied: {applied}", cycle_log)
    return applied


def update_changelog(cycle, findings, applied, cycle_log):
    log(">> Updating CHANGELOG...", cycle_log)
    changelog = PROJECT_ROOT / "docs" / "CHANGELOG-2026-05.md"
    if not changelog.exists() or applied == 0:
        log("  No changelog update needed", cycle_log)
        return
    entry = f"\n### Continuous Audit Cycle {cycle} — {datetime.now(timezone.utc).isoformat()}\n"
    for f in findings:
        if f.get("auto_fix"):
            entry += f"- **{f['id']}**: {f['msg'][:100]}\n"
    content = changelog.read_text()
    lines = content.split("\n")
    insert_idx = 0
    for i, line in enumerate(lines):
        if line.startswith("## "):
            insert_idx = i + 1
            break
    lines.insert(insert_idx, entry)
    changelog.write_text("\n".join(lines))
    log("  CHANGELOG updated", cycle_log)


def run_single_cycle(cycle):
    timestamp = datetime.now(timezone.utc).strftime("%Y%m%d-%H%M%S")
    cycle_log_path = LOG_DIR / f"cycle-{cycle:04d}-{timestamp}.log"
    master_log = LOG_DIR / "master.log"
    with open(cycle_log_path, "w") as cycle_log, open(master_log, "a") as master:
        log(f"\n{'='*60}", cycle_log)
        log(f"CYCLE {cycle} / {MAX_CYCLES}", cycle_log)
        log(f"Timestamp: {datetime.now(timezone.utc).isoformat()}", cycle_log)
        log(f"{'='*60}", cycle_log)
        bf_findings = run_brownfield_checks(cycle_log)
        mem_findings = run_memory_checks(cycle_log)
        ver_findings = run_version_checks(cycle_log)
        gitnexus = run_gitnexus_analyze(cycle_log)
        all_findings = bf_findings + mem_findings + ver_findings
        critical = sum(1 for f in all_findings if f.get("severity") == "CRITICAL")
        high = sum(1 for f in all_findings if f.get("severity") == "HIGH")
        medium = sum(1 for f in all_findings if f.get("severity") == "MEDIUM")
        low = sum(1 for f in all_findings if f.get("severity") == "LOW")
        log(f"Summary: {critical} critical, {high} high, {medium} medium, {low} low", cycle_log)
        applied = apply_auto_remediations(all_findings, cycle_log)
        applied += apply_auto_remediations([], cycle_log)
        update_changelog(cycle, all_findings, applied, cycle_log)
        if git_has_changes():
            msg = f"chore(audit-cycle-{cycle}): continuous audit auto-remediation [{timestamp}]\n\nFindings: {critical}C/{high}H/{medium}M/{low}L\nApplied: {applied} auto-fixes"
            git_commit(msg)
            log(f"Committed: {msg.split(chr(10))[0]}", cycle_log)
        else:
            log("No changes to commit", cycle_log)
        log(f"Cycle {cycle} complete: {critical}C/{high}H/{medium}M/{low}L, {applied} fixes", master)


def load_state():
    if STATE_FILE.exists():
        return json.loads(STATE_FILE.read_text())
    return {"cycle": 0, "started_at": datetime.now(timezone.utc).isoformat()}


def save_state(state):
    STATE_FILE.write_text(json.dumps(state, indent=2))


def main():
    parser = argparse.ArgumentParser(description="TSiSIP Continuous Audit Orchestrator")
    parser.add_argument("--single-cycle", action="store_true", help="Run one cycle and exit (for systemd timer)")
    args = parser.parse_args()

    LOG_DIR.mkdir(parents=True, exist_ok=True)
    master_log = LOG_DIR / "master.log"

    state = load_state()

    if args.single_cycle:
        state["cycle"] = state.get("cycle", 0) + 1
        if state["cycle"] > MAX_CYCLES:
            with open(master_log, "a") as master:
                log(f"Max cycles reached ({MAX_CYCLES}). Skipping.", master)
            save_state(state)
            return
        run_single_cycle(state["cycle"])
        save_state(state)
        return

    # Default: foreground loop mode
    with open(master_log, "a") as master:
        log("=== TSiSIP Continuous Audit Orchestrator Started (foreground) ===", master)
        log(f"MAX_CYCLES={MAX_CYCLES}, SLEEP={SLEEP_SECONDS}s", master)

    while state["cycle"] < MAX_CYCLES:
        state["cycle"] = state["cycle"] + 1
        run_single_cycle(state["cycle"])
        save_state(state)
        if state["cycle"] < MAX_CYCLES:
            time.sleep(SLEEP_SECONDS)

    with open(master_log, "a") as master:
        log(f"=== Orchestrator complete after {MAX_CYCLES} cycles ===", master)


if __name__ == "__main__":
    main()
