# Feature 012 Implementation Plan

## Wave 0: Orchestration & Setup (Coordinator)

- [x] W0.1: Create OMK Goal for Feature 012
- [x] W0.2: Create spec directory and spec.md
- [x] W0.3: Create plan.md (this document)
- [ ] W0.4: Create tasks.md with dependency graph
- [ ] W0.5: GitNexus impact analysis on `web/`, `db/`, `docs/`
- [ ] W0.6: Verify speckit governance extensions active

## Wave 1: Architecture & Falsification (Socratic Review)

**Agentes:** `socratic-popper-reviewer`, `solution-architecture`, `data-specs`

- [ ] W1.1: Challenge premise — "Is subscriber CRUD via PHP safe enough?"
- [ ] W1.2: Validate database schema against OpenSIPS 3.6 LTS stock schema
- [ ] W1.3: Confirm tenant scoping rules do not leak across tenants
- [ ] W1.4: Verify role hierarchy (admin=5, devops=4) is sufficient for tool access
- [ ] W1.5: Falsification test: Can a devops user access subscriber of another tenant?
- [ ] W1.6: Produce architecture conformance report

## Wave 2: Core Infrastructure (Coder)

**Agentes:** `coder`

- [ ] W2.1: Create `web/common/csrf.php` — CSRF token generation/validation
- [ ] W2.2: Create `web/common/pagination.php` — Reusable pagination helper
- [ ] W2.3: Create `web/common/ha1-generator.php` — HA1/HA1-SHA256/HA1-SHA512-256 generator
- [ ] W2.4: Update `web/common/role-nav.php` — Add admin tool links
- [ ] W2.5: Update `web/common/config.php` — Add `isAdmin()` / `isDevOps()` helpers

## Wave 3: Subscriber Management (Coder)

**Agentes:** `coder`

- [ ] W3.1: Create `web/subscribers.php` — List view with pagination
- [ ] W3.2: Create `web/subscribers.php` — Create form with HA1 generation
- [ ] W3.3: Create `web/subscribers.php` — Edit form
- [ ] W3.4: Create `web/subscribers.php` — Toggle enabled/disable
- [ ] W3.5: Add tenant dropdown (from `tenants` table)
- [ ] W3.6: Add CSRF protection to all mutating operations

## Wave 4: CDR Viewer (Coder)

**Agentes:** `coder`

- [ ] W4.1: Create `web/cdr-viewer.php` — List view with pagination
- [ ] W4.2: Add date range filter (from/to)
- [ ] W4.3: Add tenant filter dropdown
- [ ] W4.4: Add call_status filter
- [ ] W4.5: Add from_user search
- [ ] W4.6: Ensure read-only (no mutating operations)

## Wave 5: Dispatcher Management (Coder)

**Agentes:** `coder`

- [ ] W5.1: Rewrite `web/dispatcher.php` — List real data from PostgreSQL
- [ ] W5.2: Add create form (setid, destination, state, weight, priority, attrs, description)
- [ ] W5.3: Add edit form
- [ ] W5.4: Add delete with confirmation
- [ ] W5.5: Add state toggle (active/inactive)
- [ ] W5.6: Add CSRF protection

## Wave 6: QA & Security Review (Reviewer)

**Agentes:** `reviewer`, `omk-security-review`

- [ ] W6.1: Review all new PHP files for SQL injection vulnerabilities
- [ ] W6.2: Review CSRF token implementation
- [ ] W6.3: Review role-based access guards
- [ ] W6.4: Review HA1 generation correctness (RFC 3261, RFC 8760)
- [ ] W6.5: Run PHP syntax check on all new files
- [ ] W6.6: Run GitNexus detect-changes and validate risk level

## Wave 7: Documentation Update (Docs Agent)

**Agentes:** `doc-forensics`, `devops-docs`

- [ ] W7.1: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` — Add OCP admin tools section
- [ ] W7.2: Update `docs/TSiSIP-CANONICAL-SPEC.md` — Add OCP capabilities section
- [ ] W7.3: Update `AGENTS.md` — Reflect OCP feature set
- [ ] W7.4: Create `specs/012-ocp-admin-tools-restoration/blueprint.md`
- [ ] W7.5: Update `reports/ocp-popperian-audit-2026-05-20.md` — Mark resolved items

## Wave 8: Build & Deploy (DevOps Agent)

**Agentes:** `devops-docs`

- [ ] W8.1: Build OCP Docker image (`docker build -t ghcr.io/.../ocp:latest`)
- [ ] W8.2: Push to GHCR
- [ ] W8.3: Deploy to VPS (`docker compose -f docker-compose.vps.yml up -d ocp`)
- [ ] W8.4: Validate AC1-AC12 on VPS
- [ ] W8.5: Verify nginx config and URL accessibility

## Wave 9: Git Commit & Closure (Release Agent)

**Agentes:** `release`

- [ ] W9.1: Stage all modified/new files
- [ ] W9.2: Write conventional commit message
- [ ] W9.3: Push to GitHub
- [ ] W9.4: Update OMK Goal with evidence
- [ ] W9.5: Close Feature 012 in OMK

## Dependency Graph

```
W0 (Setup) → W1 (Arch) → W2 (Infra)
                          ↓
                    W3 (Subscribers) ─┐
                    W4 (CDR) ─────────┼→ W6 (QA) → W7 (Docs) → W8 (Deploy) → W9 (Git)
                    W5 (Dispatcher) ──┘
```
