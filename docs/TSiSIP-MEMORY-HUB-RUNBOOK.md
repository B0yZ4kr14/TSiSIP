# TSiSIP Memory Hub Operator Runbook

**Feature**: 019 — Spec Kit Memory Hub Integration  
**Version**: 1.0.0  
**Date**: 2026-05-19  

---

## 1. Overview

This runbook describes how to operate the Spec Kit Memory Hub (`memory-md` v0.8.5) for the TSiSIP project.

---

## 2. Prerequisites

- Node.js 20+ (v26.1.0 tested with warnings)
- npm 10+
- `memory-md` extension installed in `.specify/extensions/memory-md/`
- SQLite cache built at least once

---

## 3. Daily Operations

### 3.1 Check Memory Health

```bash
cd /path/to/TSiSIP
node .specify/extensions/memory-md/dist/bin/speckit-memory.js audit-memory
```

Expected output:
```
Memory Audit
Issues: 0
Stale entries: 0
Missing files: 0
Orphaned rows: 0
No cache issues found.
```

### 3.2 Refresh Index After Memory Changes

If you edit any file in `docs/memory/` or `.specify/memory/`:

```bash
node .specify/extensions/memory-md/dist/bin/speckit-memory.js refresh-memory
```

Or force a full rebuild:

```bash
node .specify/extensions/memory-md/dist/bin/speckit-memory.js rebuild-memory
```

### 3.3 Search Memory

```bash
node .specify/extensions/memory-md/dist/bin/speckit-memory.js search-memory "topology hiding"
```

---

## 4. Per-Feature Workflow

### 4.1 Before Planning a New Feature

1. Generate synthesis for the feature directory:
   ```bash
   node .specify/extensions/memory-md/dist/bin/speckit-memory.js synthesize \
     --feature specs/NNN-feature-name
   ```
2. Read the generated `specs/NNN-feature-name/memory-synthesis.md`.
3. Check `docs/memory/DECISIONS.md` for conflicting decisions.
4. Check `docs/memory/BUGS.md` for known pitfalls.

### 4.2 After Completing a Feature

1. Propose memory captures for durable lessons:
   ```bash
   # Manual: edit docs/memory/DECISIONS.md or BUGS.md
   # Then refresh index
   node .specify/extensions/memory-md/dist/bin/speckit-memory.js refresh-memory
   ```
2. Update `docs/memory/WORKLOG.md` with session summary.
3. Run audit to verify index integrity.

---

## 5. Troubleshooting

### 5.1 "No such file or directory" for dist/bin/speckit-memory.js

**Cause**: Extension not built.  
**Fix**:
```bash
cd .specify/extensions/memory-md
npm install
npm run build
```

### 5.2 "SQLite cache out of date" warnings

**Cause**: Memory files changed since last index.  
**Fix**:
```bash
node .specify/extensions/memory-md/dist/bin/speckit-memory.js rebuild-memory
```

### 5.3 Search returns no results

**Cause**: Index empty or query too specific.  
**Fix**:
1. Verify index exists: `ls .spec-kit-memory/memory.sqlite`
2. Rebuild index: `rebuild-memory`
3. Try broader query terms

### 5.4 Token report shows 0% reduction

**Cause**: Feature has no existing memory synthesis; all files are read.  
**Fix**: Normal for first run. Reduction improves as synthesis is reused.

---

## 6. Security Checklist

- [ ] `.spec-kit-memory/` is in `.gitignore`
- [ ] `secrets/` is NOT indexed
- [ ] `db/init/03-seed-data.sql` is NOT indexed
- [ ] No secrets committed to `docs/memory/*.md`
- [ ] All memory captures approved by human reviewer
- [ ] Audit runs clean (0 issues, 0 stale, 0 orphaned)

---

## 7. Contact

- Security questions: `docs/security/019-agent-memory-governance.md`
- Technical questions: `docs/security/019-memory-hub-security-assessment.md`
- Extension docs: `.specify/extensions/memory-md/README.md`
