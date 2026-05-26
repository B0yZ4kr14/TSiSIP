#!/usr/bin/env python3
"""
Speckit Agent Orchestrator Router Test
Scores capabilities from index.json against representative prompts.
"""

import json
import os
import re
from datetime import datetime, timezone

INDEX_PATH = ".specify/extensions/orchestrator/index.json"
RESULTS_PATH = ".specify/extensions/orchestrator/routing-test-results.md"
CACHE_PATH = ".specify/extensions/orchestrator/route-cache.json"
THRESHOLD = 0.5
TOP_N = 5

PROMPTS = [
    "I want to create a specification for a new SIP trunk failover feature",
    "Scan the codebase for security vulnerabilities and configuration issues",
    "Generate an implementation plan for TLS certificate rotation",
    "Run brownfield scan on the OpenSIPS configuration",
    "Set up the project constitution and architecture rules",
]


def load_index(path):
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)
    return data.get("capabilities", [])


def tokenize(text):
    """Lowercase, extract alphabetic tokens, remove common stopwords."""
    text = text.lower()
    tokens = re.findall(r"[a-z]+", text)
    stopwords = {
        "the", "a", "an", "and", "or", "for", "to", "in", "on", "at", "of", "with",
        "from", "by", "is", "are", "was", "were", "be", "been", "being", "have",
        "has", "had", "do", "does", "did", "will", "would", "could", "should",
        "may", "might", "must", "shall", "can", "need", "dare", "ought", "used",
        "i", "you", "he", "she", "it", "we", "they", "me", "him", "her", "us",
        "them", "my", "your", "his", "its", "our", "their", "this", "that", "these",
        "those", "am", "so", "if", "out", "up", "down", "over", "under", "again",
        "further", "then", "once", "here", "there", "when", "where", "why", "how",
        "all", "each", "few", "more", "most", "other", "some", "such", "no", "nor",
        "not", "only", "own", "same", "than", "too", "very", "just", "now", "want",
        "new", "run", "set", "create", "generate", "use",
    }
    return [t for t in tokens if t not in stopwords and len(t) > 1]


def clean_id(cid):
    if ":" in cid:
        return cid.split(":", 1)[1]
    return cid


def get_keywords(cap):
    """Extract meaningful keywords from triggers and cleaned id."""
    kw = set()
    for t in cap.get("triggers", []):
        kw.update(tokenize(t))
    kw.update(tokenize(clean_id(cap.get("id", ""))))
    # Remove generic framework noise
    noise = {"speckit", "omk", "sf", "dotnet", "vibecode", "canon", "fx", "git",
             "maqa", "jira", "azure", "trello", "linear", "claude", "opencode",
             "squad", "github", "workflow", "extension", "skill", "agent", "docs",
             "core", "command", "definition", "execution", "reference"}
    kw -= noise
    return kw


def get_description(cap):
    parts = []
    parts.append(cap.get("name", ""))
    parts.extend(cap.get("triggers", []))
    return " ".join(parts)


# Synonym / stem groups — words that share semantic meaning
SYNONYM_GROUPS = [
    {"specification", "specify", "spec", "specs"},
    {"rotation", "rotate", "rotating"},
    {"implementation", "implement", "implementing"},
    {"scan", "scanning", "scanner", "audit", "auditing", "inspect", "inspection", "review", "reviewing"},
    {"configuration", "config", "configure", "configuring"},
    {"constitution", "constitutional"},
    {"architecture", "architect", "architectural"},
    {"rule", "rules"},
    {"plan", "planning", "planner"},
    {"security", "secure", "securing"},
    {"vulnerability", "vulnerabilities"},
    {"feature", "features"},
    {"issue", "issues"},
]


def canonical_form(word):
    """Return the canonical form of a word based on synonym groups."""
    for group in SYNONYM_GROUPS:
        if word in group:
            return min(group)  # deterministic representative
    return word


def words_match(w1, w2):
    """Check if two words match (exact or canonical synonym)."""
    if w1 == w2:
        return True
    return canonical_form(w1) == canonical_form(w2)


def count_matches(set_a, set_b):
    """Count matches between two token sets using synonym groups."""
    matched = 0
    used = set()
    for a in set_a:
        for b in set_b:
            if b in used:
                continue
            if words_match(a, b):
                matched += 1
                used.add(b)
                break
    return matched


def score_capability(cap, prompt_raw):
    prompt_words = set(tokenize(prompt_raw))
    prompt_raw_lower = prompt_raw.lower()

    # --- Keyword match (0.4) ---
    keywords = get_keywords(cap)
    if keywords:
        matched = count_matches(prompt_words, keywords)
        keyword_score = matched / len(keywords)
        # Penalize very short keyword sets to avoid single-word dominance
        if len(keywords) <= 2:
            keyword_score *= 0.7
    else:
        keyword_score = 0.0

    # --- Description match (0.3) ---
    desc = get_description(cap)
    desc_words = set(tokenize(desc))
    desc_words.update(tokenize(cap.get("name", "")))
    desc_words.update(tokenize(clean_id(cap.get("id", ""))))
    if desc_words:
        matched_desc = count_matches(prompt_words, desc_words)
        desc_score = matched_desc / len(prompt_words) if prompt_words else 0.0
    else:
        desc_score = 0.0

    # --- Name match (0.2) ---
    name = cap.get("name", "").lower()
    name_words = set(tokenize(name))
    if name_words:
        matched_name = count_matches(prompt_words, name_words)
        name_score = matched_name / len(name_words)
    else:
        name_score = 0.0

    # Exact substring boost
    if name and name in prompt_raw_lower:
        name_score = max(name_score, 0.95)
    cid_clean = clean_id(cap.get("id", "")).lower()
    if cid_clean and cid_clean in prompt_raw_lower:
        name_score = max(name_score, 0.9)

    # --- Type bonus (0.1) ---
    ctype = cap.get("type", "").lower()
    prompt_word_count = len(prompt_words)
    if ctype == "workflow":
        type_score = 1.0 if prompt_word_count > 5 else 0.6
    elif ctype in ("skill", "extension", "extension-command", "opencode-command"):
        type_score = 0.8 if prompt_word_count > 3 else 0.7
    elif ctype == "core-command":
        type_score = 0.5 if prompt_word_count > 5 else 1.0
    else:
        type_score = 0.6

    total = (
        0.4 * keyword_score
        + 0.3 * desc_score
        + 0.2 * name_score
        + 0.1 * type_score
    )
    return round(min(total, 1.0), 4)


def route_prompt(prompt, capabilities):
    scored = []
    for cap in capabilities:
        s = score_capability(cap, prompt)
        scored.append((s, cap))
    scored.sort(key=lambda x: (-x[0], x[1].get("name", "")))
    filtered = [(s, c) for s, c in scored if s >= THRESHOLD]
    return filtered[:TOP_N]


def format_results(prompt, results):
    lines = []
    lines.append(f"### Prompt: \"{prompt}\"")
    lines.append("")
    if not results:
        lines.append("🤷 No confident match found (threshold: 0.5)")
        lines.append("")
        lines.append("Suggestions:")
        lines.append("  - Try rephrasing your request")
        lines.append("  - Run /speckit.agent-orchestrator.index to refresh the index")
        lines.append("  - Use 'specify extension search <query>' to browse available extensions")
        lines.append("")
        return "\n".join(lines)

    lines.append("🎯 Routing Results:")
    lines.append("")
    lines.append("| Rank | Score | Command / Capability | Type | Source |")
    lines.append("|------|-------|----------------------|------|--------|")
    for rank, (score, cap) in enumerate(results, 1):
        cmd = cap.get("id", "N/A")
        ctype = cap.get("type", "N/A")
        source = cap.get("source", "N/A")
        lines.append(f"| {rank} | {score:.2f} | `{cmd}` | {ctype} | {source} |")
    lines.append("")
    top_score, top_cap = results[0]
    if top_score >= 0.8:
        lines.append(f"💡 **Suggested:** Run `/{top_cap.get('id', 'N/A')}` to proceed")
    elif top_score >= 0.5:
        lines.append(f"💡 **Top match:** `/{top_cap.get('id', 'N/A')}` (confidence: {top_score:.2f})")
    lines.append("")
    return "\n".join(lines)


def main():
    os.chdir("/home/b0yz4kr14/Projects/TSiSIP")
    capabilities = load_index(INDEX_PATH)
    print(f"📋 Loaded capability index: {len(capabilities)} capabilities")

    all_results = {}
    md_lines = [
        "# Speckit Agent Orchestrator — Routing Test Results",
        "",
        "**Project:** TSiSIP  ",
        f"**Index:** `{INDEX_PATH}`  ",
        f"**Total Capabilities:** {len(capabilities)}  ",
        f"**Threshold:** {THRESHOLD}  ",
        f"**Top-N:** {TOP_N}  ",
        f"**Generated:** {datetime.now(timezone.utc).isoformat()}  ",
        "",
        "---",
        "",
    ]

    for prompt in PROMPTS:
        print(f"\n🎯 Routing: \"{prompt}\"")
        results = route_prompt(prompt, capabilities)
        all_results[prompt] = [
            {"score": s, "capability": c} for s, c in results
        ]
        md_lines.append(format_results(prompt, results))
        md_lines.append("---")
        md_lines.append("")

        for rank, (score, cap) in enumerate(results, 1):
            print(f"  {rank}. {score:.2f}  {cap.get('id', 'N/A')}  ({cap.get('type', 'N/A')})")
        if not results:
            print("  (no results above threshold)")
            scored = []
            for cap in capabilities:
                s = score_capability(cap, prompt)
                scored.append((s, cap))
            scored.sort(key=lambda x: (-x[0], x[1].get("name", "")))
            for rank, (score, cap) in enumerate(scored[:3], 1):
                print(f"    near-miss {rank}. {score:.2f}  {cap.get('id', 'N/A')}  ({cap.get('type', 'N/A')})")

    # Write route cache
    cache = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "threshold": THRESHOLD,
        "top_n": TOP_N,
        "cache": all_results,
    }
    with open(CACHE_PATH, "w", encoding="utf-8") as f:
        json.dump(cache, f, indent=2)
    print(f"\n💾 Route cache saved to: {CACHE_PATH}")

    # Write markdown report
    with open(RESULTS_PATH, "w", encoding="utf-8") as f:
        f.write("\n".join(md_lines))
    print(f"📝 Routing test results saved to: {RESULTS_PATH}")


if __name__ == "__main__":
    main()
