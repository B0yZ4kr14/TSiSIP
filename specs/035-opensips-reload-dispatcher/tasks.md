# Feature 035 Tasks

## Phase 1: Backend APIs

- [x] T35.1.1 Create `web/api/v1/dispatcher-crud.php` (GET/POST/PUT/DELETE)
- [x] T35.1.2 Add SIP URI validation helper
- [x] T35.1.3 Create `web/api/v1/dispatcher-reload.php` with MI ds_reload
- [x] T35.1.4 Implement DB transaction + MI rollback on failure
- [x] T35.1.5 Create `web/api/v1/dispatcher-probe.php` (OPTIONS probe)
- [x] T35.1.6 Create `dispatcher_change_log` table migration
- [x] T35.1.7 Create `web/api/v1/dispatcher-rollback.php`
- [x] T35.1.8 Create `web/api/v1/dispatcher-import.php` (CSV parser)
- [x] T35.1.9 Create `web/api/v1/dispatcher-export.php` (CSV generator)
- [x] T35.1.10 Add audit logging to all endpoints

## Phase 2: Frontend Admin UI

- [x] T35.2.1 Enhance `web/dispatcher.php` with CRUD table
- [ ] T35.2.2 Add "Apply Changes" reload button with state management
- [ ] T35.2.3 Add probe status icons to destination rows
- [ ] T35.2.4 Add destination modal with form validation
- [ ] T35.2.5 Add delete confirmation modal
- [ ] T35.2.6 Add "History" tab with rollback UI
- [ ] T35.2.7 Add CSV import modal with preview
- [ ] T35.2.8 Add CSV export button

## Phase 3: Integration & Security

- [x] T35.3.1 Enforce admin/devops role on all mutating endpoints
- [ ] T35.3.2 Implement rate limit (5 reloads/min per session)
- [x] T35.3.3 Add CSRF validation to AJAX endpoints
- [ ] T35.3.4 Add `dispatcher.reload` event to SSE stream
- [ ] T35.3.5 Verify transaction safety under concurrent reloads

## Phase 4: Testing & Validation

- [ ] T35.4.1 Write PHP endpoint unit tests with mock MI
- [ ] T35.4.2 Write CSV parser validation tests
- [ ] T35.4.3 Write end-to-end add → reload → verify test
- [ ] T35.4.4 Write OPTIONS probe test (valid/invalid destinations)
- [ ] T35.4.5 Write rollback test
- [ ] T35.4.6 Write bulk import test
- [ ] T35.4.7 Write role-based access test
- [ ] T35.4.8 Performance test: reload with 50 destinations < 2s
- [ ] T35.4.9 Update operator runbook with dispatcher management section

## Dependencies

- T35.1.3 blocked until MI HTTP is verified reachable from OCP (C4)
- T35.1.6 blocked until DB migration strategy is confirmed
- T35.2.1 depends on T35.1.1
- T35.3.4 depends on Feature 034 SSE stream
- T35.4.3 depends on T35.3.3
