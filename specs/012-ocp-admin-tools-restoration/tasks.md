# Feature 012 Tasks

## Wave 0: Orchestration & Setup
- [x] T0.1: Create OMK Goal
- [x] T0.2: Create spec.md
- [x] T0.3: Create plan.md
- [ ] T0.4: Create tasks.md (this file)
- [ ] T0.5: Run GitNexus impact analysis on web/ db/ docs/
- [ ] T0.6: Verify speckit governance extensions

## Wave 1: Architecture & Falsification
- [ ] T1.1: Socratic review — challenge subscriber CRUD safety
- [ ] T1.2: Validate stock OpenSIPS 3.6 schema alignment
- [ ] T1.3: Confirm tenant isolation rules
- [ ] T1.4: Verify role hierarchy sufficiency
- [ ] T1.5: Falsification test — cross-tenant access
- [ ] T1.6: Architecture conformance report

## Wave 2: Core Infrastructure
- [ ] T2.1: Create `web/common/csrf.php`
- [ ] T2.2: Create `web/common/pagination.php`
- [ ] T2.3: Create `web/common/ha1-generator.php`
- [ ] T2.4: Update `web/common/role-nav.php`
- [ ] T2.5: Update `web/common/config.php`

## Wave 3: Subscriber Management
- [ ] T3.1: `subscribers.php` — list view with pagination
- [ ] T3.2: `subscribers.php` — create form + HA1 generation
- [ ] T3.3: `subscribers.php` — edit form
- [ ] T3.4: `subscribers.php` — toggle enabled
- [ ] T3.5: Tenant dropdown integration
- [ ] T3.6: CSRF protection on subscribers

## Wave 4: CDR Viewer
- [ ] T4.1: `cdr-viewer.php` — list view with pagination
- [ ] T4.2: Date range filter
- [ ] T4.3: Tenant filter
- [ ] T4.4: Call status filter
- [ ] T4.5: from_user search

## Wave 5: Dispatcher Management
- [ ] T5.1: Rewrite `dispatcher.php` with real DB data
- [ ] T5.2: Create dispatcher form
- [ ] T5.3: Edit dispatcher form
- [ ] T5.4: Delete dispatcher with confirmation
- [ ] T5.5: State toggle (active/inactive)
- [ ] T5.6: CSRF protection on dispatcher

## Wave 6: QA & Security Review
- [ ] T6.1: SQL injection audit
- [ ] T6.2: CSRF review
- [ ] T6.3: RBAC review
- [ ] T6.4: HA1 correctness review
- [ ] T6.5: PHP syntax check all new files
- [ ] T6.6: GitNexus detect-changes

## Wave 7: Documentation
- [ ] T7.1: Update operator runbook
- [ ] T7.2: Update canonical spec
- [ ] T7.3: Update AGENTS.md
- [ ] T7.4: Create blueprint.md
- [ ] T7.5: Update audit report

## Wave 8: Build & Deploy
- [ ] T8.1: Build OCP Docker image
- [ ] T8.2: Push to GHCR
- [ ] T8.3: Deploy to VPS
- [ ] T8.4: Validate acceptance criteria
- [ ] T8.5: Verify nginx/URLs

## Wave 9: Git Commit & Closure
- [ ] T9.1: Stage files
- [ ] T9.2: Conventional commit
- [ ] T9.3: Push to GitHub
- [ ] T9.4: Update OMK Goal evidence
- [ ] T9.5: Close Feature 012
