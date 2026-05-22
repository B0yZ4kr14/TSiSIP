#!/usr/bin/env python3
"""Refresh Spec Kit agent governance projection for the current project."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path
from typing import Any


MEMORY_PATH = Path(".specify/memory/agent-governance.md")
TEMPLATE_PATH = Path(".specify/extensions/agent-governance/templates/agent-governance-template.md")
INTEGRATION_JSON = Path(".specify/integration.json")
INIT_OPTIONS_JSON = Path(".specify/init-options.json")

MARKER_START = "<!-- SPECKIT GOVERNANCE START -->"
MARKER_END = "<!-- SPECKIT GOVERNANCE END -->"

CONTEXT_FILES = {
    "agy": "AGENTS.md",
    "amp": "AGENTS.md",
    "auggie": ".augment/rules/specify-rules.md",
    "bob": "AGENTS.md",
    "claude": "CLAUDE.md",
    "codebuddy": "CODEBUDDY.md",
    "codex": "AGENTS.md",
    "copilot": ".github/copilot-instructions.md",
    "cursor-agent": ".cursor/rules/specify-rules.mdc",
    "devin": "AGENTS.md",
    "forge": "AGENTS.md",
    "gemini": "GEMINI.md",
    "generic": "AGENTS.md",
    "goose": "AGENTS.md",
    "iflow": "IFLOW.md",
    "junie": ".junie/AGENTS.md",
    "kilocode": ".kilocode/rules/specify-rules.md",
    "kimi": "KIMI.md",
    "kiro-cli": "AGENTS.md",
    "lingma": ".lingma/rules/specify-rules.md",
    "opencode": "AGENTS.md",
    "pi": "AGENTS.md",
    "qodercli": "QODER.md",
    "qwen": "QWEN.md",
    "roo": ".roo/rules/specify-rules.md",
    "shai": "SHAI.md",
    "tabnine": "TABNINE.md",
    "trae": ".trae/rules/project_rules.md",
    "vibe": "AGENTS.md",
    "windsurf": ".windsurf/rules/specify-rules.md",
}


def main() -> int:
    root = Path.cwd()
    if not (root / ".specify").is_dir():
        print("Error: .specify/ not found. Run from a Spec Kit project root.", file=sys.stderr)
        return 1

    created_memory = ensure_memory(root)
    state = read_json(root / INTEGRATION_JSON)
    init_options = read_json(root / INIT_OPTIONS_JSON)
    target = resolve_target(root, state, init_options)
    projection = render_projection(root, target, state, created_memory)
    action = write_projection(target, projection)
    remove_stale_sections(root, target, init_options, state)

    print(f"Source memory: {MEMORY_PATH.as_posix()}")
    print(f"Target context: {rel(root, target)}")
    print(f"Memory: {'created' if created_memory else 'existing'}")
    print(f"Projection: {action}")
    return 0


def ensure_memory(root: Path) -> bool:
    memory = root / MEMORY_PATH
    if memory.exists():
        return False
    template = root / TEMPLATE_PATH
    if not template.exists():
        raise SystemExit(f"Error: template not found: {TEMPLATE_PATH.as_posix()}")
    memory.parent.mkdir(parents=True, exist_ok=True)
    memory.write_bytes(template.read_bytes())
    return True


def read_json(path: Path) -> dict[str, Any]:
    if not path.exists():
        return {}
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError, UnicodeDecodeError):
        return {}
    return data if isinstance(data, dict) else {}


def resolve_target(root: Path, state: dict[str, Any], init_options: dict[str, Any]) -> Path:
    for value in (
        init_options.get("context_file"),
        CONTEXT_FILES.get(default_integration(state) or ""),
        "AGENTS.md",
    ):
        target = safe_project_path(root, value)
        if target is not None:
            return target
    return root / "AGENTS.md"


def safe_project_path(root: Path, value: Any) -> Path | None:
    if not isinstance(value, str) or not value.strip():
        return None
    raw = Path(value.strip())
    candidate = raw if raw.is_absolute() else root / raw
    try:
        candidate.resolve(strict=False).relative_to(root.resolve())
    except (OSError, ValueError):
        return None
    return candidate


def default_integration(state: dict[str, Any]) -> str | None:
    for key in ("default_integration", "integration"):
        value = state.get(key)
        if isinstance(value, str) and value.strip():
            return value.strip()
    installed = state.get("installed_integrations")
    if isinstance(installed, list):
        for value in installed:
            if isinstance(value, str) and value.strip():
                return value.strip()
    return None


def installed_integrations(state: dict[str, Any]) -> list[str]:
    seen: set[str] = set()
    values = state.get("installed_integrations")
    if not isinstance(values, list):
        values = []
    default = default_integration(state)
    ordered = ([default] if default else []) + values
    result: list[str] = []
    for value in ordered:
        if not isinstance(value, str) or not value.strip():
            continue
        clean = value.strip()
        if clean not in seen:
            seen.add(clean)
            result.append(clean)
    return result


def render_projection(root: Path, target: Path, state: dict[str, Any], created_memory: bool) -> str:
    memory = root / MEMORY_PATH
    default_key = default_integration(state) or "unknown"
    installed = installed_integrations(state)
    style = projection_style(target)
    lines = [
        MARKER_START,
        "## Repository Agent Governance",
        "",
        f"Agent governance SSOT: `{MEMORY_PATH.as_posix()}`.",
        style_lead(style),
        "",
        "## Governance Domains",
        "- Agent Governance Domain: `.specify/memory/agent-governance.md` is the SSOT for agent collaboration rules, tool and MCP permissions, write boundaries, and skill invocation contracts.",
        "- Project Governance Domain: independent SSOT, managed outside this projection.",
        "- Keep governance domains decoupled; do not encode upstream/downstream dependencies between them.",
        "",
        "## Resolved Repository Context",
        f"- Active Integration: {default_key}",
        f"- Resolved Constraints File: {rel(root, target)}",
        f"- Installed Integrations: {', '.join(installed) if installed else 'none'}",
        f"- Governance Memory: {MEMORY_PATH.as_posix()} ({'created' if created_memory else 'present'})",
        f"- Skills: {', '.join(scan_skills(root)) or 'none'}",
        f"- MCP Configs: {', '.join(scan_mcp_configs(root)) or 'none'}",
        f"- Extensions Config: .specify/extensions.yml ({extensions_status(root)})",
        "",
        "## Authority Order",
        "1. Current user instruction",
        "2. Agent governance domain rules from `.specify/memory/agent-governance.md`",
        "3. User-authored repository instructions for agent behavior",
        "4. Skill-local `SKILL.md`",
        "5. Tool and MCP defaults",
        "",
        "## Non-Negotiable Execution Gates",
        "- Before editing implementation files, verify the active change has the required project-governance artifacts for implementation.",
        "- If any required project-governance artifact is missing, stop implementation and run the owning project-governance workflow before editing implementation files.",
        "- Do not treat bug fixes, refactors, or small code changes as exceptions to the implementation gate.",
        "- Do not modify governance, CI, MCP config, secrets, permissions, or tool settings unless the user explicitly requests that change.",
        "- Before any mutating MCP call or external write, obtain explicit user intent and report the target, action, and expected effect.",
        "- Before handoff, report changed files, commands run, validation results, and unresolved risks.",
        "",
        "## Write Boundaries",
        *section_or_default(memory, "## Write Boundaries", write_boundary_default(style)),
        "",
        "## MCP And External Tool Policy",
        *section_or_default(memory, "## MCP Policy", mcp_default(style)),
        "",
        "## Skill Usage Policy",
        *section_or_default(memory, "## Skill Contract", skill_default(style)),
        "",
        "## Required Handoff Report",
        *section_or_default(memory, "## Validation", handoff_default(style)),
        MARKER_END,
        "",
    ]
    return "\n".join(lines)


def write_projection(target: Path, projection: str) -> str:
    existing = target.read_text(encoding="utf-8-sig") if target.exists() else ""
    had_section = MARKER_START in existing and MARKER_END in existing
    updated = upsert_section(existing, projection)
    if target.suffix == ".mdc":
        updated = ensure_mdc_frontmatter(updated)
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(normalize_newlines(updated), encoding="utf-8")
    return "replaced" if had_section else "inserted"


def upsert_section(content: str, projection: str) -> str:
    start = content.find(MARKER_START)
    end = content.find(MARKER_END, start if start != -1 else 0)
    if start != -1 and end != -1 and end > start:
        end += len(MARKER_END)
        if end < len(content) and content[end] == "\r":
            end += 1
        if end < len(content) and content[end] == "\n":
            end += 1
        return content[:start] + projection + content[end:]
    if content and not content.endswith("\n"):
        content += "\n"
    return content + ("\n" if content else "") + projection


def remove_stale_sections(root: Path, active: Path, init_options: dict[str, Any], state: dict[str, Any]) -> None:
    paths = {root / "AGENTS.md"}
    for value in CONTEXT_FILES.values():
        target = safe_project_path(root, value)
        if target is not None:
            paths.add(target)
    init_target = safe_project_path(root, init_options.get("context_file"))
    if init_target is not None:
        paths.add(init_target)
    for path in paths:
        if same_path(path, active):
            continue
        remove_section(path)


def remove_section(path: Path) -> None:
    if not path.exists():
        return
    content = path.read_text(encoding="utf-8-sig")
    start = content.find(MARKER_START)
    end = content.find(MARKER_END, start if start != -1 else 0)
    if start == -1 or end == -1 or end <= start:
        return
    removal_end = end + len(MARKER_END)
    if removal_end < len(content) and content[removal_end] == "\r":
        removal_end += 1
    if removal_end < len(content) and content[removal_end] == "\n":
        removal_end += 1
    removal_start = start
    if removal_start > 1 and content[removal_start - 1] == "\n" and content[removal_start - 2] == "\n":
        removal_start -= 1
    updated = normalize_newlines(content[:removal_start] + content[removal_end:])
    if not updated.strip() or (path.suffix == ".mdc" and re.match(r"^---\n.*?\n---\s*$", updated, re.DOTALL)):
        path.unlink()
    else:
        path.write_text(updated, encoding="utf-8")


def section_or_default(memory: Path, heading: str, default: list[str]) -> list[str]:
    section = extract_section(memory, heading)
    return section or default


def extract_section(path: Path, heading: str) -> list[str]:
    try:
        lines = normalize_newlines(path.read_text(encoding="utf-8-sig")).splitlines()
    except (OSError, UnicodeDecodeError):
        return []
    capture = False
    result: list[str] = []
    for line in lines:
        if line.strip() == heading:
            capture = True
            continue
        if capture and line.startswith("## "):
            break
        if capture and line.strip():
            result.append(line)
    return result


def projection_style(path: Path) -> str:
    rel_path = path.as_posix()
    if rel_path.endswith(".github/copilot-instructions.md"):
        return "copilot"
    if path.suffix == ".mdc" or "/rules/" in rel_path:
        return "rule"
    return "agent"


def style_lead(style: str) -> str:
    if style == "copilot":
        return "Use these as concise Copilot custom instructions for this repository."
    if style == "rule":
        return "Apply these repository rules before planning, editing, or using tools."
    return "Follow these repository instructions when working in this project."


def write_boundary_default(style: str) -> list[str]:
    if style == "rule":
        return ["- Stay inside the active task scope.", "- Preserve user-authored edits.", "- Follow `.specify/memory/agent-governance.md`."]
    return ["- Follow `.specify/memory/agent-governance.md` for the full repository write policy.", "- Keep edits inside the active task scope and preserve user changes."]


def mcp_default(style: str) -> list[str]:
    return ["- MCP tools are read-only unless the user explicitly authorizes a mutating action.", "- External writes must name the target, action, and expected effect before execution."]


def skill_default(style: str) -> list[str]:
    return ["- Use skill-local `SKILL.md` contracts when a skill is invoked.", "- Do not infer write scope beyond the paths declared by the active skill."]


def handoff_default(style: str) -> list[str]:
    return ["- Report changed files.", "- Report commands run.", "- Report tests or validation results.", "- Report unresolved risks."]


def scan_feature_specs(root: Path) -> str:
    specs = root / "specs"
    if not specs.is_dir():
        return "none"
    entries = []
    for feature in sorted(path for path in specs.iterdir() if path.is_dir()):
        statuses = [f"{name}:{'present' if (feature / name).exists() else 'missing'}" for name in ("spec.md", "plan.md", "tasks.md")]
        entries.append(f"{rel(root, feature)} ({', '.join(statuses)})")
    return ", ".join(entries) if entries else "none"


def scan_skills(root: Path) -> list[str]:
    return sorted(rel(root, path) for path in root.rglob("SKILL.md") if not ignored(path))


def scan_mcp_configs(root: Path) -> list[str]:
    names = {".mcp.json", "mcp.json", "mcp.yml", "mcp.yaml", "mcp.config.json"}
    return sorted(
        rel(root, path)
        for path in root.rglob("*")
        if path.is_file() and not ignored(path) and (path.name in names or "mcp" in path.name.lower())
    )


def ignored(path: Path) -> bool:
    return any(part in {".git", "__pycache__", ".venv", "node_modules"} for part in path.parts)


def extensions_status(root: Path) -> str:
    path = root / ".specify/extensions.yml"
    if not path.exists():
        return "missing"
    return "present"


def exists(root: Path, value: str) -> str:
    return "present" if (root / value).exists() else "missing"


def ensure_mdc_frontmatter(content: str) -> str:
    stripped = content.lstrip()
    if not stripped.startswith("---"):
        return "---\nalwaysApply: true\n---\n\n" + content
    match = re.match(r"^(---[ \t]*\n)(.*?)(\n---[ \t]*)(\n|$)(.*)", stripped, re.DOTALL)
    if not match:
        return "---\nalwaysApply: true\n---\n\n" + content
    opening, frontmatter, closing, sep, rest = match.groups()
    if re.search(r"(?m)^[ \t]*alwaysApply[ \t]*:[ \t]*true[ \t]*$", frontmatter):
        return content
    if re.search(r"(?m)^[ \t]*alwaysApply[ \t]*:", frontmatter):
        frontmatter = re.sub(r"(?m)^([ \t]*)alwaysApply[ \t]*:.*$", r"\1alwaysApply: true", frontmatter, count=1)
    elif frontmatter.strip():
        frontmatter += "\nalwaysApply: true"
    else:
        frontmatter = "alwaysApply: true"
    return f"{opening}{frontmatter}{closing}{sep}{rest}"


def same_path(left: Path, right: Path) -> bool:
    try:
        return left.resolve(strict=False) == right.resolve(strict=False)
    except OSError:
        return left.absolute() == right.absolute()


def rel(root: Path, path: Path) -> str:
    try:
        return path.relative_to(root).as_posix()
    except ValueError:
        return path.as_posix()


def normalize_newlines(content: str) -> str:
    return content.replace("\r\n", "\n").replace("\r", "\n")


if __name__ == "__main__":
    raise SystemExit(main())
