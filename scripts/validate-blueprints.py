#!/usr/bin/env python3
"""
TSiSIP Blueprint Validation Script
Validates all blueprint.md files against:
1. Their corresponding spec.md, plan.md, tasks.md
2. Actual implementation files referenced in the blueprint
3. Drift detection: blueprint says one thing but implementation differs
"""

import os
import re
import sys
from pathlib import Path
from datetime import datetime
from dataclasses import dataclass, field
from typing import List, Dict, Optional, Tuple

PROJECT_ROOT = Path("/home/b0yz4kr14/Projects/TSiSIP")
SPECS_DIR = PROJECT_ROOT / "specs"
REPORTS_DIR = PROJECT_ROOT / "reports"
REPORT_DATE = "2026-05-19"


@dataclass
class BlueprintCheck:
    check_name: str
    status: str  # PASS, FAIL, WARN, SKIP, INFO
    message: str
    details: List[str] = field(default_factory=list)


@dataclass
class SpecValidation:
    spec_num: str
    spec_name: str
    blueprint_exists: bool = False
    spec_exists: bool = False
    plan_exists: bool = False
    tasks_exists: bool = False
    blueprint_size: int = 0
    spec_size: int = 0
    plan_size: int = 0
    tasks_size: int = 0
    checks: List[BlueprintCheck] = field(default_factory=list)
    files_referenced: List[str] = field(default_factory=list)
    files_found: List[str] = field(default_factory=list)
    files_missing: List[str] = field(default_factory=list)
    new_files: List[str] = field(default_factory=list)
    modified_files: List[str] = field(default_factory=list)
    todo_markers_found: int = 0
    todo_markers_expected: int = 0
    before_after_checks: List[Dict] = field(default_factory=list)
    requirement_ids: List[str] = field(default_factory=list)
    drift_items: List[str] = field(default_factory=list)


def get_spec_dirs() -> List[Tuple[str, Path]]:
    """Get all spec directories sorted numerically."""
    specs = []
    if SPECS_DIR.exists():
        for d in sorted(SPECS_DIR.iterdir()):
            if d.is_dir():
                num = d.name.split("-")[0]
                if num.isdigit():
                    specs.append((num, d))
    return sorted(specs, key=lambda x: int(x[0]))


def extract_file_references(blueprint_text: str) -> Tuple[List[str], List[str], List[str]]:
    """Extract file references from blueprint. Returns (all, new, modified)."""
    all_files = []
    new_files = []
    modified_files = []

    # Pattern: **File**: `path` (new|modify|delete)
    pattern1 = r"\*\*File\*\*:\s*`([^`]+)`\s*\((new|modify|delete|verify)\)"
    for match in re.finditer(pattern1, blueprint_text, re.IGNORECASE):
        filepath = match.group(1).strip()
        action = match.group(2).lower()
        all_files.append(filepath)
        if action in ("new",):
            new_files.append(filepath)
        elif action in ("modify", "delete"):
            modified_files.append(filepath)

    # Pattern: - `path` (new|modify|delete)
    pattern2 = r"^\s*[-*]\s*`([^`]+)`\s*\((new|modify|delete|verify)\)"
    for match in re.finditer(pattern2, blueprint_text, re.MULTILINE | re.IGNORECASE):
        filepath = match.group(1).strip()
        action = match.group(2).lower()
        if filepath not in all_files:
            all_files.append(filepath)
            if action in ("new",):
                new_files.append(filepath)
            elif action in ("modify", "delete"):
                modified_files.append(filepath)

    # Pattern: `path`: (new|modify|delete)
    pattern3 = r"`([^`]+)`\s*:\s*\((new|modify|delete|verify)\)"
    for match in re.finditer(pattern3, blueprint_text, re.IGNORECASE):
        filepath = match.group(1).strip()
        action = match.group(2).lower()
        if filepath not in all_files and not filepath.startswith("http"):
            all_files.append(filepath)
            if action in ("new",):
                new_files.append(filepath)
            elif action in ("modify", "delete"):
                modified_files.append(filepath)

    # Generic file path extraction for lines that look like file references
    # e.g., `docker/admin_api/Dockerfile`, `web/common/header.php`
    pattern4 = r"`((?:[a-zA-Z0-9_-]+/)+[a-zA-Z0-9_\-\.]+)`"
    for match in re.finditer(pattern4, blueprint_text):
        filepath = match.group(1).strip()
        if (
            filepath not in all_files
            and "/" in filepath
            and not filepath.startswith("http")
            and not filepath.endswith(".md")
            and not any(x in filepath for x in ["FR-", "T", "AC", "SR-", "R"])
        ):
            # Check if it looks like a real file path
            if any(
                filepath.startswith(prefix)
                for prefix in [
                    "docker/",
                    "web/",
                    "db/",
                    "opensips/",
                    "deploy/",
                    "tests/",
                    "scripts/",
                    "build/",
                    "design/",
                    "docs/",
                    ".github/",
                    "docker-compose",
                    "Dockerfile",
                    "Makefile",
                    ".env",
                ]
            ):
                all_files.append(filepath)

    # Deduplicate
    all_files = list(dict.fromkeys(all_files))
    new_files = list(dict.fromkeys(new_files))
    modified_files = list(dict.fromkeys(modified_files))

    return all_files, new_files, modified_files


def extract_requirement_ids(blueprint_text: str) -> List[str]:
    """Extract requirement IDs like FR-XXX-YYY, ACX, R1, etc."""
    reqs = []
    # FR-NNN-NNN pattern
    fr_pattern = r"(FR-\d{3}-\d{3}[A-Z]?)"
    for match in re.finditer(fr_pattern, blueprint_text):
        req = match.group(1)
        if req not in reqs:
            reqs.append(req)
    # AC patterns
    ac_pattern = r"\b(AC\d+)\b"
    for match in re.finditer(ac_pattern, blueprint_text):
        req = match.group(1)
        if req not in reqs:
            reqs.append(req)
    # R patterns like R1, R2
    r_pattern = r"\b(R\d+)\b"
    for match in re.finditer(r_pattern, blueprint_text):
        req = match.group(1)
        if req not in reqs:
            reqs.append(req)
    return reqs


def extract_before_after_blocks(blueprint_text: str) -> List[Dict]:
    """Extract Before/After code blocks from blueprint for drift checking."""
    blocks = []
    # Find sections with Before and After
    before_after_pattern = r"###\s+([T\d\.]+):\s+(.+?)\n.*?\*\*File\*\*:\s*`([^`]+)`.*?\*\*Before\*\*.*?(?:\n```[\w]*\n(.*?)\n```|\n`([^`]+)`).*?\*\*After\*\*.*?(?:\n```[\w]*\n(.*?)\n```|\n`([^`]+)`"

    # Simpler approach: find task headers and look for Before/After nearby
    task_pattern = r"###\s+([T\d\.]+):\s+(.+?)(?=###|\Z)"
    for match in re.finditer(task_pattern, blueprint_text, re.DOTALL):
        task_id = match.group(1).strip()
        task_content = match.group(2)

        file_match = re.search(r"\*\*File\*\*:\s*`([^`]+)`", task_content)
        if not file_match:
            continue
        filepath = file_match.group(1)

        # Find Before block
        before_match = re.search(
            r"\*\*Before\*\*.*?(?:\(lines?\s+[^)]+\))?\n\n?```[\w]*\n(.*?)\n```",
            task_content,
            re.DOTALL,
        )
        after_match = re.search(
            r"\*\*After\*\*.*?(?:\(lines?\s+[^)]+\))?\n\n?```[\w]*\n(.*?)\n```",
            task_content,
            re.DOTALL,
        )

        if before_match and after_match:
            before_code = before_match.group(1).strip()
            after_code = after_match.group(1).strip()
            blocks.append(
                {
                    "task_id": task_id,
                    "filepath": filepath,
                    "before": before_code,
                    "after": after_code,
                }
            )

    return blocks


def check_after_implementation(block: Dict, project_root: Path) -> Tuple[bool, str]:
    """Check if the 'After' code from blueprint is present in the actual file."""
    filepath = project_root / block["filepath"]
    if not filepath.exists():
        return False, f"File not found: {block['filepath']}"

    try:
        with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()
    except Exception as e:
        return False, f"Could not read file: {e}"

    after_code = block["after"]
    # Try exact match first
    if after_code in content:
        return True, "After code block found exactly in file"

    # Try line-by-line fuzzy match (ignore whitespace differences)
    after_lines = [line.strip() for line in after_code.split("\n") if line.strip()]
    content_lines = [line.strip() for line in content.split("\n") if line.strip()]

    # Check if key lines from after are present
    key_lines_found = 0
    key_lines_total = len(after_lines)
    for line in after_lines:
        # Skip very short lines or comments that might vary
        if len(line) < 10 or line.startswith("#"):
            continue
        if line in content_lines:
            key_lines_found += 1

    if key_lines_total > 0 and key_lines_found / key_lines_total >= 0.5:
        return True, f"Key lines found ({key_lines_found}/{key_lines_total})"

    # Check for significant substrings (lines > 30 chars)
    significant_lines = [line for line in after_lines if len(line) > 30]
    sig_found = sum(1 for line in significant_lines if line in content_lines)
    if significant_lines and sig_found / len(significant_lines) >= 0.3:
        return True, f"Significant lines found ({sig_found}/{len(significant_lines)})"

    return False, f"After code not detected (key lines: {key_lines_found}/{key_lines_total})"


def count_todo_markers(filepath: Path) -> int:
    """Count TODO/FIXME/XXX markers in a file."""
    if not filepath.exists():
        return 0
    try:
        with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()
    except Exception:
        return 0
    todos = len(re.findall(r"\bTODO\b", content, re.IGNORECASE))
    fixmes = len(re.findall(r"\bFIXME\b", content, re.IGNORECASE))
    xxxs = len(re.findall(r"\bXXX\b", content))
    return todos + fixmes + xxxs


def check_requirements_in_spec(requirements: List[str], spec_text: str) -> List[Tuple[str, bool]]:
    """Check if requirement IDs from blueprint are found in spec.md."""
    results = []
    for req in requirements:
        found = req in spec_text
        results.append((req, found))
    return results


def check_tasks_alignment(blueprint_text: str, tasks_text: str) -> List[Tuple[str, bool]]:
    """Check if tasks referenced in blueprint exist in tasks.md."""
    # Extract task IDs from blueprint like T1.1, T2.3, etc.
    bp_task_ids = set(re.findall(r"\b(T\d+\.\d+)\b", blueprint_text))
    tasks_task_ids = set(re.findall(r"\b(T\d+\.\d+)\b", tasks_text))

    results = []
    for tid in sorted(bp_task_ids):
        results.append((tid, tid in tasks_task_ids))
    return results


def validate_spec(spec_num: str, spec_dir: Path) -> SpecValidation:
    """Validate a single spec's blueprint."""
    spec_name = spec_dir.name
    result = SpecValidation(spec_num=spec_num, spec_name=spec_name)

    blueprint_path = spec_dir / "blueprint.md"
    spec_path = spec_dir / "spec.md"
    plan_path = spec_dir / "plan.md"
    tasks_path = spec_dir / "tasks.md"

    # Check existence of core files
    result.blueprint_exists = blueprint_path.exists()
    result.spec_exists = spec_path.exists()
    result.plan_exists = plan_path.exists()
    result.tasks_exists = tasks_path.exists()

    if result.blueprint_exists:
        result.blueprint_size = blueprint_path.stat().st_size
    if result.spec_exists:
        result.spec_size = spec_path.stat().st_size
    if result.plan_exists:
        result.plan_size = plan_path.stat().st_size
    if result.tasks_exists:
        result.tasks_size = tasks_path.stat().st_size

    if not result.blueprint_exists:
        result.checks.append(
            BlueprintCheck(
                "Blueprint Existence",
                "FAIL",
                f"blueprint.md not found in {spec_dir.name}",
            )
        )
        return result

    # Read blueprint
    with open(blueprint_path, "r", encoding="utf-8", errors="ignore") as f:
        blueprint_text = f.read()

    # Read other files if they exist
    spec_text = ""
    tasks_text = ""
    if result.spec_exists:
        with open(spec_path, "r", encoding="utf-8", errors="ignore") as f:
            spec_text = f.read()
    if result.tasks_exists:
        with open(tasks_path, "r", encoding="utf-8", errors="ignore") as f:
            tasks_text = f.read()

    # Extract file references
    all_files, new_files, modified_files = extract_file_references(blueprint_text)
    result.files_referenced = all_files
    result.new_files = new_files
    result.modified_files = modified_files

    # Check file existence
    for filepath in all_files:
        full_path = PROJECT_ROOT / filepath
        if full_path.exists():
            result.files_found.append(filepath)
        else:
            result.files_missing.append(filepath)

    # Check TODO markers in new files
    for filepath in new_files:
        full_path = PROJECT_ROOT / filepath
        if full_path.exists():
            todo_count = count_todo_markers(full_path)
            result.todo_markers_found += todo_count
            if todo_count == 0:
                # New file without TODOs might be over-implemented or a config file
                # Only flag if it looks like a source file
                if any(
                    filepath.endswith(ext)
                    for ext in [".py", ".sh", ".js", ".php", ".go", ".rs", ".java"]
                ):
                    result.checks.append(
                        BlueprintCheck(
                            "TODO Markers",
                            "WARN",
                            f"New source file {filepath} has no TODO/FIXME markers — may be over-implemented",
                        )
                    )

    # Check Before/After blocks
    before_after_blocks = extract_before_after_blocks(blueprint_text)
    result.before_after_checks = []
    for block in before_after_blocks:
        implemented, msg = check_after_implementation(block, PROJECT_ROOT)
        result.before_after_checks.append(
            {
                "task_id": block["task_id"],
                "filepath": block["filepath"],
                "implemented": implemented,
                "message": msg,
            }
        )

    # Extract requirements
    result.requirement_ids = extract_requirement_ids(blueprint_text)

    # Check requirements traceability to spec.md
    if result.spec_exists and result.requirement_ids:
        req_results = check_requirements_in_spec(result.requirement_ids, spec_text)
        missing_in_spec = [r for r, found in req_results if not found]
        if missing_in_spec:
            result.checks.append(
                BlueprintCheck(
                    "Spec Traceability",
                    "WARN",
                    f"Requirements from blueprint not found in spec.md: {', '.join(missing_in_spec[:5])}",
                )
            )

    # Check tasks alignment
    if result.tasks_exists:
        task_results = check_tasks_alignment(blueprint_text, tasks_text)
        missing_tasks = [t for t, found in task_results if not found]
        if missing_tasks:
            result.checks.append(
                BlueprintCheck(
                    "Tasks Alignment",
                    "WARN",
                    f"Tasks referenced in blueprint but not found in tasks.md: {', '.join(missing_tasks[:10])}",
                )
            )

    # Drift detection: check if blueprint references files that don't exist
    if result.files_missing:
        result.checks.append(
            BlueprintCheck(
                "File Existence",
                "FAIL" if len(result.files_missing) > 3 else "WARN",
                f"{len(result.files_missing)} referenced file(s) not found on disk",
                result.files_missing,
            )
        )

    # Drift: Before/After blocks not implemented
    not_implemented = [b for b in result.before_after_checks if not b["implemented"]]
    if not_implemented:
        result.checks.append(
            BlueprintCheck(
                "Implementation Drift",
                "FAIL" if len(not_implemented) > 2 else "WARN",
                f"{len(not_implemented)} blueprint change(s) not detected in implementation",
                [f"{b['task_id']} ({b['filepath']}): {b['message']}" for b in not_implemented[:5]],
            )
        )

    # Overall status check
    if not result.checks:
        result.checks.append(
            BlueprintCheck(
                "Overall",
                "PASS",
                f"Blueprint {spec_name} validation passed with no issues",
            )
        )

    return result


def generate_report(results: List[SpecValidation]) -> str:
    """Generate the markdown validation report."""
    lines = []
    lines.append("# TSiSIP Blueprint Validation Report")
    lines.append("")
    lines.append(f"**Date**: {REPORT_DATE}")
    lines.append(f"**Specs Validated**: {len(results)}")
    lines.append(f"**Project Root**: `{PROJECT_ROOT}`")
    lines.append("")
    lines.append("---")
    lines.append("")

    # Executive Summary
    total_checks = 0
    pass_count = 0
    warn_count = 0
    fail_count = 0
    total_files_ref = 0
    total_files_found = 0
    total_files_missing = 0
    total_drift = 0

    for r in results:
        for c in r.checks:
            total_checks += 1
            if c.status == "PASS":
                pass_count += 1
            elif c.status == "WARN":
                warn_count += 1
            elif c.status == "FAIL":
                fail_count += 1
        total_files_ref += len(r.files_referenced)
        total_files_found += len(r.files_found)
        total_files_missing += len(r.files_missing)
        total_drift += len([b for b in r.before_after_checks if not b.get("implemented", True)])

    lines.append("## Executive Summary")
    lines.append("")
    lines.append(f"| Metric | Value |")
    lines.append(f"|---|---|")
    lines.append(f"| Total Specs | {len(results)} |")
    lines.append(f"| Blueprints Found | {sum(1 for r in results if r.blueprint_exists)} |")
    lines.append(f"| Specs Found | {sum(1 for r in results if r.spec_exists)} |")
    lines.append(f"| Plans Found | {sum(1 for r in results if r.plan_exists)} |")
    lines.append(f"| Tasks Found | {sum(1 for r in results if r.tasks_exists)} |")
    lines.append(f"| Files Referenced | {total_files_ref} |")
    lines.append(f"| Files Found | {total_files_found} |")
    lines.append(f"| Files Missing | {total_files_missing} |")
    lines.append(f"| Before/After Drift Items | {total_drift} |")
    lines.append(f"| Checks Passed | {pass_count} |")
    lines.append(f"| Checks Warning | {warn_count} |")
    lines.append(f"| Checks Failed | {fail_count} |")
    lines.append("")

    # Status table per spec
    lines.append("## Per-Spec Validation Summary")
    lines.append("")
    lines.append("| Spec | Name | Blueprint | Spec | Plan | Tasks | Files Ref | Found | Missing | Drift | Status |")
    lines.append("|---|---|---|---|---|---|---|---|---|---|---|")
    for r in results:
        has_fail = any(c.status == "FAIL" for c in r.checks)
        has_warn = any(c.status == "WARN" for c in r.checks)
        if has_fail:
            status = "❌ FAIL"
        elif has_warn:
            status = "⚠️ WARN"
        else:
            status = "✅ PASS"

        drift_count = len([b for b in r.before_after_checks if not b.get("implemented", True)])
        lines.append(
            f"| {r.spec_num} | {r.spec_name} |"
            f" {'✅' if r.blueprint_exists else '❌'} |"
            f" {'✅' if r.spec_exists else '❌'} |"
            f" {'✅' if r.plan_exists else '❌'} |"
            f" {'✅' if r.tasks_exists else '❌'} |"
            f" {len(r.files_referenced)} |"
            f" {len(r.files_found)} |"
            f" {len(r.files_missing)} |"
            f" {drift_count} |"
            f" {status} |"
        )
    lines.append("")

    # Detailed findings per spec
    lines.append("## Detailed Findings")
    lines.append("")

    for r in results:
        lines.append(f"### Spec {r.spec_num}: {r.spec_name}")
        lines.append("")

        # Artifact presence
        lines.append("#### Artifacts")
        lines.append(f"- **blueprint.md**: {'✅ Present' if r.blueprint_exists else '❌ Missing'} ({r.blueprint_size} bytes)")
        lines.append(f"- **spec.md**: {'✅ Present' if r.spec_exists else '❌ Missing'} ({r.spec_size} bytes)")
        lines.append(f"- **plan.md**: {'✅ Present' if r.plan_exists else '❌ Missing'} ({r.plan_size} bytes)")
        lines.append(f"- **tasks.md**: {'✅ Present' if r.tasks_exists else '❌ Missing'} ({r.tasks_size} bytes)")
        lines.append("")

        # Checks
        if r.checks:
            lines.append("#### Validation Checks")
            lines.append("")
            for c in r.checks:
                icon = "✅" if c.status == "PASS" else "⚠️" if c.status == "WARN" else "❌" if c.status == "FAIL" else "ℹ️"
                lines.append(f"**{icon} {c.check_name}** — *{c.status}*")
                lines.append(f"{c.message}")
                if c.details:
                    for d in c.details[:10]:
                        lines.append(f"- {d}")
                    if len(c.details) > 10:
                        lines.append(f"- ... and {len(c.details) - 10} more")
                lines.append("")

        # Files
        if r.files_referenced:
            lines.append("#### Referenced Files")
            lines.append("")
            if r.files_missing:
                lines.append("**Missing files:**")
                for f in r.files_missing:
                    lines.append(f"- ❌ `{f}`")
                lines.append("")
            new_found = [f for f in r.new_files if f in r.files_found]
            mod_found = [f for f in r.modified_files if f in r.files_found]
            if new_found:
                lines.append(f"**New files found ({len(new_found)}):**")
                for f in new_found[:20]:
                    todo_count = count_todo_markers(PROJECT_ROOT / f)
                    todo_info = f" ({todo_count} TODOs)" if todo_count > 0 else ""
                    lines.append(f"- ✅ `{f}`{todo_info}")
                if len(new_found) > 20:
                    lines.append(f"- ... and {len(new_found) - 20} more")
                lines.append("")
            if mod_found:
                lines.append(f"**Modified files found ({len(mod_found)}):**")
                for f in mod_found[:20]:
                    lines.append(f"- ✅ `{f}`")
                if len(mod_found) > 20:
                    lines.append(f"- ... and {len(mod_found) - 20} more")
                lines.append("")

        # Before/After drift
        if r.before_after_checks:
            lines.append("#### Before/After Implementation Checks")
            lines.append("")
            for b in r.before_after_checks:
                icon = "✅" if b["implemented"] else "❌"
                lines.append(f"{icon} **{b['task_id']}** (`{b['filepath']}`) — {b['message']}")
            lines.append("")

        # Requirements
        if r.requirement_ids:
            lines.append(f"#### Requirements Referenced ({len(r.requirement_ids)})")
            lines.append(", ".join(r.requirement_ids[:20]))
            if len(r.requirement_ids) > 20:
                lines.append(f", ... and {len(r.requirement_ids) - 20} more")
            lines.append("")

        lines.append("---")
        lines.append("")

    # Appendix: Drift Analysis
    lines.append("## Appendix A: Cross-Spec Drift Analysis")
    lines.append("")
    lines.append("This section highlights patterns of drift detected across multiple specs.")
    lines.append("")

    # Find specs with missing files
    specs_with_missing = [(r.spec_num, r.spec_name, r.files_missing) for r in results if r.files_missing]
    if specs_with_missing:
        lines.append("### A.1 Missing Files Across Specs")
        lines.append("")
        for num, name, missing in specs_with_missing:
            lines.append(f"**{num} — {name}**:")
            for m in missing:
                lines.append(f"- `{m}`")
            lines.append("")

    # Find specs with unimplemented Before/After
    specs_with_drift = [
        (r.spec_num, r.spec_name, [b for b in r.before_after_checks if not b["implemented"]])
        for r in results
        if any(not b["implemented"] for b in r.before_after_checks)
    ]
    if specs_with_drift:
        lines.append("### A.2 Implementation Drift (Before/After Mismatches)")
        lines.append("")
        for num, name, drift in specs_with_drift:
            lines.append(f"**{num} — {name}** ({len(drift)} items):")
            for d in drift:
                lines.append(f"- `{d['task_id']}` in `{d['filepath']}`: {d['message']}")
            lines.append("")

    lines.append("---")
    lines.append("")
    lines.append("*Report generated by TSiSIP Blueprint Validation*")
    lines.append(f"*Timestamp: {datetime.now().isoformat()}*")

    return "\n".join(lines)


def main():
    print("TSiSIP Blueprint Validation starting...")
    spec_dirs = get_spec_dirs()
    print(f"Found {len(spec_dirs)} spec directories")

    results = []
    for spec_num, spec_dir in spec_dirs:
        print(f"  Validating {spec_num}: {spec_dir.name}...")
        result = validate_spec(spec_num, spec_dir)
        results.append(result)

    # Generate report
    print("Generating report...")
    report = generate_report(results)

    REPORTS_DIR.mkdir(parents=True, exist_ok=True)
    report_path = REPORTS_DIR / f"blueprint-validate-report-{REPORT_DATE}.md"
    with open(report_path, "w", encoding="utf-8") as f:
        f.write(report)

    print(f"Report written to: {report_path}")

    # Summary
    total_fail = sum(1 for r in results for c in r.checks if c.status == "FAIL")
    total_warn = sum(1 for r in results for c in r.checks if c.status == "WARN")
    total_pass = sum(1 for r in results for c in r.checks if c.status == "PASS")
    print(f"\nSummary: {total_pass} passed, {total_warn} warnings, {total_fail} failures")

    return 0 if total_fail == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
