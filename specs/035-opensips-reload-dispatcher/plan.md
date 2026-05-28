# Feature 035 Implementation Plan

## Phase 1: Backend APIs (Days 1-2)

### 1.1 Dispatcher CRUD Endpoint
- Create `web/api/v1/dispatcher-crud.php`
- Methods: GET (list), POST (add), PUT (update), DELETE (remove)
- Validate SIP URI format before DB insert
- Validate setid is integer and exists
- Return JSON with affected rows and new destination ID

### 1.2 Dispatcher Reload Endpoint
- Create `web/api/v1/dispatcher-reload.php`
- Call MI `ds_reload` via `miHttpCall('ds_reload', [])`
- If MI returns success, commit DB transaction
- If MI fails, rollback DB transaction and return error
- Rate limit: 5 calls per minute per session

### 1.3 Pre-Reload OPTIONS Probe
- Create `web/api/v1/dispatcher-probe.php`
- Send SIP OPTIONS to destination URI via `sipsak` or PHP socket
- Timeout: 3 seconds
- Return probe result (200 OK / timeout / error)
- Called automatically before add/update if `probe=true` parameter set

### 1.4 Rollback Endpoint
- Create `web/api/v1/dispatcher-rollback.php`
- Maintain `dispatcher_change_log` table with JSON snapshot of previous state
- On rollback, restore previous state to dispatcher table and trigger `ds_reload`
- Keep last 10 changes per set

### 1.5 Import/Export Endpoints
- Create `web/api/v1/dispatcher-import.php`
- Accept CSV upload with columns: setid, destination, flags, priority, description
- Validate each row, skip invalid, report errors
- Create `web/api/v1/dispatcher-export.php`
- Return CSV of all dispatcher destinations

### 1.6 Audit Integration
- Log all CRUD, reload, rollback, import operations via `logAuditEvent()`
- Include before/after JSON snapshots for rollback support

## Phase 2: Frontend Admin UI (Days 3-4)

### 2.1 Dispatcher Management Page
- Enhance existing `web/dispatcher.php` (or create new admin view)
- Table showing all sets and destinations
- Columns: Set ID, Destination URI, State (Active/Inactive/Probing), Priority, Weight, Description, Actions
- Inline editing for priority and description
- Add destination modal with form validation
- Delete confirmation with warning about active calls

### 2.2 Reload Button
- Prominent "Apply Changes" button in page header
- Disabled until there are uncommitted changes
- Shows spinner during reload
- Shows success/error toast after reload completes
- Auto-refreshes table after successful reload

### 2.3 Probe Status Indicator
- Each destination row shows probe status icon
- Green: OPTIONS 200 OK
- Yellow: No response / timeout
- Red: Connection refused / error
- Manual "Re-probe" button per destination

### 2.4 Rollback UI
- "History" tab showing last 10 changes
- Each change: timestamp, user, action, before/after diff
- "Restore" button per change
- Confirmation modal before rollback

### 2.5 Import/Export UI
- "Import CSV" button with file picker
- Preview of parsed rows before import
- Error report for invalid rows
- "Export CSV" button downloads full dispatcher table

## Phase 3: Integration & Security (Day 5)

### 3.1 Role-Based Access
- Enforce admin/devops only for all mutating endpoints
- Readonly users see table but no add/edit/delete/reload buttons
- Hide rollback history from non-admin users

### 3.2 Rate Limiting
- Implement session-based rate limit for reload endpoint
- Return 429 with Retry-After header if exceeded

### 3.3 Transaction Safety
- Wrap DB changes in transaction
- Only commit after MI `ds_reload` succeeds
- On failure, rollback and return detailed error

### 3.4 SSE Integration
- Ensure Feature 034 SSE stream reflects new destinations within 5s of reload
- Add `dispatcher.reload` event to SSE stream when reload occurs

## Phase 4: Testing & Validation (Day 6)

### 4.1 Unit Tests
- PHP endpoint tests with mock MI responses
- CSV parser validation tests
- SIP URI validation tests
- Rollback logic tests

### 4.2 Integration Tests
- End-to-end add → reload → verify via MI test
- OPTIONS probe test (valid and invalid destinations)
- Rollback test
- Bulk import test
- Role-based access test

### 4.3 Performance Tests
- Reload with 50 destinations completes in < 2s
- Bulk import of 100 destinations completes in < 5s
- SSE stream handles reload event without disconnect

## Deliverables

| Artifact | Path |
|---|---|
| CRUD endpoint | `web/api/v1/dispatcher-crud.php` |
| Reload endpoint | `web/api/v1/dispatcher-reload.php` |
| Probe endpoint | `web/api/v1/dispatcher-probe.php` |
| Rollback endpoint | `web/api/v1/dispatcher-rollback.php` |
| Import endpoint | `web/api/v1/dispatcher-import.php` |
| Export endpoint | `web/api/v1/dispatcher-export.php` |
| Admin UI | `web/dispatcher.php` (enhanced) |
| Change log table | `db/init/04-dispatcher-changelog.sql` |
| CSS enhancements | `web/tsisip/css/tsisip-theme.css` |
| Integration tests | `tests/integration/test_dispatcher_mgmt.py` |
| Operator runbook update | `docs/TSiSIP-OPERATOR-RUNBOOK.md` |
