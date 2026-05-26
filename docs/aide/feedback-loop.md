# AIDE Feedback Loop Report — TSiSIP

> Generated: 2026-05-24  
> Scope: Document gaps, process issues, command adaptations, and actionable recommendations for the AIDE workflow on the TSiSIP project.

---

## 1. Document Gaps

### What Exists
- `docs/aide/vision.md` — created recently; covers purpose, goals, capabilities, architecture principles, non-goals, and success criteria. Accurate and comprehensive.

### What Is Missing

| Document | Status | Impact |
|----------|--------|--------|
| `docs/aide/roadmap.md` | **Missing** | AIDE Step 2 prerequisite for progress tracking and queue generation. Without it, there is no staged view of how the 24 existing specs map to deliverable milestones. |
| `docs/aide/progress.md` | **Missing** | AIDE Step 3 prerequisite for queue generation. No centralized tracking of which specs/features are Complete, In Progress, or Planned. |
| `docs/aide/queue/queue-001.md` | **Missing** | AIDE Step 4 output. No prioritized work items ready for `create-item` / `execute-item`. |
| `docs/aide/items/` | **Missing** | AIDE Step 5 output directory. No detailed work item specifications exist in the AIDE format. |

### Deeper Gap: Existing Specs Are Invisible to AIDE

The project has **23 feature specifications** in `specs/001/` through `specs/024/` (note: **014 is missing** from the sequence). Each contains:
- `spec.md` — requirements and acceptance criteria
- `plan.md` — implementation plan
- `tasks.md` — task breakdown with checkboxes
- Optional: `blueprint.md`, `memory.md`, `security-constraints.md`, etc.

However, **none of this is integrated into the AIDE workflow**. The AIDE commands (`create-queue`, `create-item`, `execute-item`) expect to drive work from scratch, not ingest existing Spec Kit artifacts. This creates a dual-track problem:
- **Track A**: Spec Kit SDD specs in `specs/NNN-*/` (rich, but AIDE-blind)
- **Track B**: AIDE docs in `docs/aide/` (empty except vision)

### Specific Data Integrity Issue

- **Feature 024** (`specs/024-brownfield-remediation/spec.md`) declares `Status: Completed`, yet its `tasks.md` shows **all 32 tasks unchecked** (`[ ]`). This is a spec-to-task tracking divergence that would break `execute-item` auto-selection logic (it relies on progress status to pick the next item).

---

## 2. Process Issues

### Issue P1: AIDE Assumes Greenfield; TSiSIP Is Brownfield

The AIDE 7-step workflow (`create-vision` -> `create-roadmap` -> `create-progress` -> `create-queue` -> `create-item` -> `execute-item` -> `feedback-loop`) is designed for **new projects starting from scratch**. TSiSIP already has:
- 24 committed feature specs with SDD artifacts
- A running Docker Compose stack
- Active brownfield scan -> remediation cycles
- Evidence directories, reports, and memory synthesis files

**Impact**: Running AIDE commands naively would generate redundant roadmap/progress artifacts that ignore 90% of existing work. The user would need to manually reconcile AIDE state with `specs/` after every step.

### Issue P2: speckit-analyze Prerequisite Check Bug

Reported behavior: `speckit-analyze` prerequisite check returns:
```json
{ "ok": false, "missing": [""] }
```

- `ok: false` blocks analysis despite files existing.
- `missing: [""]` contains an **empty string**, suggesting a null/undefined file path is being pushed into the missing array during prerequisite validation.
- This is a **tooling bug**, not a project issue, but it blocks the standard Spec Kit quality gate.

### Issue P3: Brownfield Scan -> Remediation Loop Is Not Closing

Brownfield scan reports show **recurring findings** across multiple dates:

| Finding | First Seen | Status in Latest Scan (2026-05-24) |
|---------|-----------|-----------------------------------|
| RTPengine control socket binds to `0.0.0.0:22222` in `docker-compose.vps.yml` | Earlier scans | **CRITICAL** — still open |
| Hard-coded Docker network IPs in test scripts | Earlier scans | **MEDIUM** — still open (now in `scripts/test-invite-407.sh`) |
| Self-signed cert expiry monitoring | Earlier scans | **HIGH** — still open |
| `.env.example` missing variables relative to `docker-compose.vps.yml` | Earlier scans | Partially addressed but gap remains |

**Root cause**: There is no **automated gate** that prevents a feature from being marked complete while brownfield findings exist. The execute-item command does not require a pre-merge brownfield scan.

### Issue P4: Missing Spec 014

The sequence jumps from `013-brownfield-follow-up` to `015-auto-tls-certificate-rotation`. Spec `014` does not exist. This gap:
- Breaks sequential assumptions in scripts that iterate `001..024`
- Suggests either a deleted/merged spec or a numbering error
- Should be documented if intentional

---

## 3. Command Adaptations Needed

### Adaptation A: `create-item.md` — Add Infrastructure-Specific Testing Prerequisites

The current `create-item.md` template has generic testing prerequisites. For TSiSIP, every work item that touches Docker/OpenSIPS/PostgreSQL should require:

**Additional Required Sections:**
1. **Docker Validation**
   - `docker compose config` must pass with zero errors
   - All modified Dockerfiles must build successfully (`docker build -t tsisip/<service>:latest -f docker/<service>/Dockerfile .`)

2. **OpenSIPS Config Validation**
   - `opensips -c -f /etc/opensips/opensips.cfg` must pass inside the built image
   - Required env vars: `DB_HOST`, `DB_NAME`, `DB_USER`, `HOST_PUBLIC_IP`, `OPENSIPS_LISTEN_IP`, `RTPENGINE_HOST`
   - Secrets must be mounted at `/run/secrets/`

3. **PostgreSQL Schema Validation**
   - `psql -U opensips -d opensips -c "\dt"` must show expected tables
   - Any new schema files in `db/init/` must be idempotent (`CREATE TABLE IF NOT EXISTS`, `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`)

4. **SIP-Level Smoke Tests**
   - `sipsak -s sip:opensips:5060` must return `SIP/2.0 200 OK`
   - Unauthenticated `INVITE` must return `401 Unauthorized` or `407 Proxy Authentication Required`

5. **Brownfield Scan Gate**
   - Run `speckit-brownfield-scan` against changed files
   - Block completion if any new HIGH/CRITICAL/MEDIUM findings are introduced

### Adaptation B: `execute-item.md` — Add Brownfield Scan Gate and Progress Sync

The execute-item command should:
1. **Before starting**: Verify the selected item's `tasks.md` checkboxes align with `spec.md` status. If `spec.md` says "Completed" but `tasks.md` is all unchecked, flag the mismatch and require reconciliation.
2. **Before marking complete**: Run a targeted brownfield scan on files modified during implementation. Fail the item if new HIGH/CRITICAL findings are introduced.
3. **After completion**: Update **both** `docs/aide/progress.md` **and** the corresponding `specs/NNN-*/tasks.md` checkboxes.

### Adaptation C: `create-queue.md` — Ingest Existing Specs Instead of Generating New Features

The queue should be generated from the **existing `specs/` directory**, not from a blank roadmap. Proposed logic:

1. Scan `specs/NNN-*/spec.md` for `Status` field
2. Treat specs with `Status: Completed` as done
3. Treat specs with `Status: In Progress` or missing status as active
4. Generate queue items from the **unchecked tasks** in `specs/NNN-*/tasks.md`
5. Number queue items sequentially starting from 001

This bridges the Spec Kit SDD artifacts into the AIDE workflow without losing existing work.

### Adaptation D: `create-roadmap.md` — Map Existing Specs to Stages

Instead of inventing new stages, the roadmap should:
1. Group existing 23 specs by functional layer (Infrastructure, Security, OCP, Observability, SIP Trunk, Remediation)
2. Mark completed stages based on `spec.md` status
3. Append new stages for future work (e.g., Feature 025+)

### Adaptation E: New Project-Specific Command — `docker-stack-validate`

Create a new AIDE command (or add to `create-item` template):
- Validates `docker-compose.yml`, `docker-compose.prod.yml`, and `docker-compose.vps.yml`
- Checks that `.env.example` documents every `${VAR}` referenced in compose files
- Verifies no secrets are committed
- Checks that Asterisk and PostgreSQL have no `ports:` published
- Checks that RTPengine control socket uses `${RTPENGINE_INTERNAL_IP}`, not `0.0.0.0`

---

## 4. Recommendations

### Immediate (Do Next)

| # | Action | Owner | Effort |
|---|--------|-------|--------|
| R1 | **Create `docs/aide/roadmap.md`** mapping existing 23 specs into 6 functional stages (Foundation, Security, OCP, Observability, SIP Trunk, Stabilization). Mark stages with completed specs as done. | AIDE / Human | 30 min |
| R2 | **Create `docs/aide/progress.md`** importing all 23 spec statuses from `specs/*/spec.md`. Use Spec Kit status if present; infer from `tasks.md` checkbox coverage otherwise. | AIDE / Human | 30 min |
| R3 | **Create `docs/aide/queue/queue-001.md`** from the **remaining open tasks** across all specs. Prioritize by: (a) CRITICAL/HIGH brownfield findings, (b) unclosed specs with unchecked tasks, (c) infrastructure gaps. | AIDE | 20 min |
| R4 | **Reconcile Feature 024** — either check off completed tasks in `tasks.md` or change `spec.md` status to "In Progress". Ensure spec status and task checkboxes are consistent. | Human | 10 min |
| R5 | **Document missing spec 014** — add a note to `docs/aide/roadmap.md` or `specs/README.md` explaining that 014 was skipped/merged into 015 or does not exist. | Human | 5 min |

### Short-Term (This Week)

| # | Action | Owner | Effort |
|---|--------|-------|--------|
| R6 | **Patch `.specify/extensions/aide/commands/create-item.md`** to add the "Infrastructure-Specific Testing Prerequisites" section (Docker validation, OpenSIPS config check, PostgreSQL schema check, SIP smoke tests, brownfield gate). | Human | 20 min |
| R7 | **Patch `.specify/extensions/aide/commands/execute-item.md`** to add: (a) pre-flight spec/tasks consistency check, (b) post-implementation brownfield scan gate, (c) dual-update of progress.md and specs/NNN/tasks.md. | Human | 20 min |
| R8 | **Patch `.specify/extensions/aide/commands/create-queue.md`** to support ingesting existing `specs/` artifacts instead of generating features from scratch. Add a "Brownfield Mode" clause. | Human | 20 min |
| R9 | **Fix `speckit-analyze` empty-string bug** — the prerequisite check returning `missing: [""]` needs a null-filter before array construction. This is a Spec Kit tooling issue; report upstream or patch locally if the tool is vendored. | Tooling | 15 min |
| R10 | **Fix CRITICAL brownfield finding B1** — change `docker-compose.vps.yml` line 65 from `--listen-ng=0.0.0.0:22222` to `--listen-ng=${RTPENGINE_INTERNAL_IP}:22222` to match AGENTS.md Section 4 and the other compose files. | Implementation | 5 min |

### Medium-Term (Next Sprint)

| # | Action | Owner | Effort |
|---|--------|-------|--------|
| R11 | **Create a new AIDE command** `speckit.aide.validate-stack` (or similar) that runs: `docker compose config`, `opensips -c`, brownfield scan on changed files, and `.env.example` parity check. Integrate into `execute-item.md` as a mandatory gate. | Human | 1 hr |
| R12 | **Add `.env.example` parity automation** — derive all `${VAR}` references from `docker-compose.vps.yml` and assert each has a commented line in `.env.example`. Currently only 3 vars are referenced in the VPS compose, but the full compose has more; this check should cover all compose files. | Implementation | 30 min |
| R13 | **Establish recurring brownfield scan gate** — add a CI step or pre-commit hook that runs `speckit-brownfield-scan` on changed files and blocks merge if new HIGH/CRITICAL/MEDIUM findings are introduced. | DevOps | 1 hr |
| R14 | **Parameterize remaining hard-coded IPs** — `scripts/test-invite-407.sh` still uses `172.19.0.4`. Apply the same `TEST_IP` env-var pattern used in Feature 024's test scripts. | Implementation | 15 min |

### Process Improvements

| # | Improvement | Rationale |
|---|-------------|-----------|
| P1 | **Single source of truth for status** — Either AIDE (`docs/aide/progress.md`) or Spec Kit (`specs/*/spec.md`) should be primary. Recommend Spec Kit as primary (it has richer artifacts) and AIDE as a consumable view regenerated from Spec Kit state. |
| P2 | **Task checkbox discipline** — Every spec marked "Completed" must have its `tasks.md` checkboxes fully checked. Make this a peer-review requirement. |
| P3 | **Brownfield scan before queue generation** — `create-queue` should run a brownfield scan and **front-load remediation items** ahead of new feature work. This prevents findings from accumulating. |
| P4 | **Queue items should reference source specs** — Each queue item should carry a `Source: specs/NNN-*/` tag so traceability is preserved between AIDE and Spec Kit artifacts. |

---

## Summary

The AIDE workflow can work for TSiSIP, but it requires **brownfield adaptation**. The biggest risk is generating a parallel track of AIDE artifacts that diverge from the rich Spec Kit specs already in `specs/`. The recommended path is:

1. **Import** existing spec state into AIDE (roadmap, progress, queue)
2. **Patch** AIDE commands for infrastructure-specific validation gates
3. **Fix** the immediate tooling bug and CRITICAL brownfield finding
4. **Run** the adapted workflow for new work (Feature 025+)

This preserves the investment in 23 existing specs while gaining the structured execution benefits of AIDE.
