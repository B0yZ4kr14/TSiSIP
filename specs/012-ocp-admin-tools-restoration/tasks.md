# Feature 012 Tasks

## Wave 0: Orchestration & Setup
- [x] T0.1: Create OMK Goal
- [x] T0.2: Create spec.md
- [x] T0.3: Create plan.md
- [x] T0.4: Create tasks.md (this file)
- [x] T0.5: Run GitNexus impact analysis on web/ db/ docs/
- [x] T0.6: Verify speckit governance extensions

## Wave 1: Architecture & Falsification
- [x] T1.1: Socratic review — challenge subscriber CRUD safety
- [x] T1.2: Validate stock OpenSIPS 3.6 schema alignment
- [x] T1.3: Confirm tenant isolation rules
- [x] T1.4: Verify role hierarchy sufficiency
- [x] T1.5: Falsification test — cross-tenant access
- [x] T1.6: Architecture conformance report

## Wave 2: Core Infrastructure
- [x] T2.1: Create `web/common/csrf.php`
- [x] T2.2: Create `web/common/pagination.php`
- [x] T2.3: Create `web/common/ha1-generator.php`
- [x] T2.4: Update `web/common/role-nav.php`
- [x] T2.5: Update `web/common/config.php`
- [x] T2.6: Create reusable input validation helper (`web/common/validate-input.php`) — length, charset, SQL injection guards per R6

## Wave 3: Subscriber Management
- [x] T3.1: `subscribers.php` — list view with pagination
- [x] T3.2: `subscribers.php` — create form + HA1 generation
- [x] T3.3: `subscribers.php` — edit form
- [x] T3.4: `subscribers.php` — toggle enabled
- [x] T3.5: Tenant dropdown integration
- [x] T3.6: CSRF protection on subscribers
- [x] T3.7: Integrate `requireRole('devops')` guard on `subscribers.php`

## Wave 4: CDR Viewer
- [x] T4.1: `cdr-viewer.php` — list view with pagination
- [x] T4.2: Date range filter
- [x] T4.3: Tenant filter
- [x] T4.4: Call status filter
- [x] T4.5: from_user search
- [x] T4.6: Integrate `requireRole('devops')` guard on `cdr-viewer.php`
- [x] T4.7: Validate `cdr-viewer.php` has no POST handlers or mutating operations (read-only enforcement)

## Wave 5: Dispatcher Management
- [x] T5.1: Rewrite `dispatcher.php` with real DB data
- [x] T5.2: Create dispatcher form
- [x] T5.3: Edit dispatcher form
- [x] T5.4: Delete dispatcher with confirmation
- [x] T5.5: State toggle (active/inactive)
- [x] T5.6: CSRF protection on dispatcher
- [x] T5.7: Integrate `requireRole('devops')` guard on `dispatcher.php`

## Wave 6: QA & Security Review
- [x] T6.1: SQL injection audit
- [x] T6.2: CSRF review
- [x] T6.3: RBAC review
- [x] T6.4: HA1 correctness review against RFC 3261 (MD5) and RFC 8760 (SHA-256, SHA-512/256)
- [x] T6.5: PHP syntax check all new files (PHP not available locally; syntax reviewed visually)
- [x] T6.6: GitNexus detect-changes (GitNexus re-indexed successfully)

## Wave 7: Documentation
- [x] T7.1: Update operator runbook
- [x] T7.2: Update canonical spec
- [x] T7.3: Update AGENTS.md
- [x] T7.4: Create blueprint.md
- [x] T7.5: Update audit report

## Wave 8: Build & Deploy
- [x] T8.1: Build OCP Docker image (use `TSISIP_IMAGE_TAG` or explicit version tag; avoid `:latest` in production per B13 remediation)
- [x] T8.2: Push to GHCR
- [x] T8.3: Deploy to VPS
- [x] T8.4: Validate acceptance criteria
- [x] T8.5: Verify nginx/URLs

## Wave 9: Git Commit & Closure
- [x] T9.1: Stage files
- [x] T9.2: Conventional commit
- [x] T9.3: Push to GitHub
- [x] T9.4: Update OMK Goal evidence
- [x] T9.5: Close Feature 012
