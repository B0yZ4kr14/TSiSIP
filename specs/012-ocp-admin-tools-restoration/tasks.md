# Feature 012 Tasks



## Phase 0: Orchestration & Setup
- [x] T001: Create OMK Goal
- [x] T002: Create spec.md
- [x] T003: Create plan.md
- [x] T004: Create tasks.md (this file)
- [x] T005: Run GitNexus impact analysis on web/ db/ docs/
- [x] T006: Verify speckit governance extensions

## Phase 1: Architecture & Falsification
- [x] T007: Socratic review — challenge subscriber CRUD safety
- [x] T008: Validate stock OpenSIPS 3.6 schema alignment
- [x] T009: Confirm tenant isolation rules
- [x] T010: Verify role hierarchy sufficiency
- [x] T011: Falsification test — cross-tenant access
- [x] T012: Architecture conformance report

## Phase 2: Core Infrastructure
- [x] T013: Create `web/common/csrf.php`
- [x] T014: Create `web/common/pagination.php`
- [x] T015: Create `web/common/ha1-generator.php`
- [x] T016: Update `web/common/role-nav.php`
- [x] T017: Update `web/common/config.php`
- [x] T018: Create reusable input validation helper (`web/common/validate-input.php`) — length, charset, SQL injection guards per R6

## Phase 3: Subscriber Management
- [x] T019: `subscribers.php` — list view with pagination
- [x] T020: `subscribers.php` — create form + HA1 generation
- [x] T021: `subscribers.php` — edit form
- [x] T022: `subscribers.php` — toggle enabled
- [x] T023: Tenant dropdown integration
- [x] T024: CSRF protection on subscribers
- [x] T025: Integrate `requireRole('devops')` guard on `subscribers.php`

## Phase 4: CDR Viewer
- [x] T026: `cdr-viewer.php` — list view with pagination
- [x] T027: Date range filter
- [x] T028: Tenant filter
- [x] T029: Call status filter
- [x] T030: from_user search
- [x] T031: Integrate `requireRole('devops')` guard on `cdr-viewer.php`
- [x] T032: Validate `cdr-viewer.php` has no POST handlers or mutating operations (read-only enforcement)

## Phase 5: Dispatcher Management
- [x] T033: Rewrite `dispatcher.php` with real DB data
- [x] T034: Create dispatcher form
- [x] T035: Edit dispatcher form
- [x] T036: Delete dispatcher with confirmation
- [x] T037: State toggle (active/inactive)
- [x] T038: CSRF protection on dispatcher
- [x] T039: Integrate `requireRole('devops')` guard on `dispatcher.php`

## Phase 6: QA & Security Review
- [x] T040: SQL injection audit
- [x] T041: CSRF review
- [x] T042: RBAC review
- [x] T043: HA1 correctness review against RFC 3261 (MD5) and RFC 8760 (SHA-256, SHA-512/256)
- [x] T044: PHP syntax check all new files (PHP not available locally; syntax reviewed visually)
- [x] T045: GitNexus detect-changes (GitNexus re-indexed successfully)

## Phase 7: Documentation
- [x] T046: Update operator runbook
- [x] T047: Update canonical spec
- [x] T048: Update AGENTS.md
- [x] T049: Create blueprint.md
- [x] T050: Update audit report

## Phase 8: Build & Deploy
- [x] T051: Build OCP Docker image (use `TSISIP_IMAGE_TAG` or explicit version tag; avoid `:latest` in production per B13 remediation)
- [x] T052: Push to GHCR
- [x] T053: Deploy to VPS
- [x] T054: Validate acceptance criteria
- [x] T055: Verify nginx/URLs

## Phase 9: Git Commit & Closure
- [x] T056: Stage files
- [x] T057: Conventional commit
- [x] T058: Push to GitHub
- [x] T059: Update OMK Goal evidence
- [x] T060: Close Feature 012
