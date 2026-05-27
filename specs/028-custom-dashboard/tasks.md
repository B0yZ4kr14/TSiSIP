# Tasks: Custom Dashboard Layouts

## Phase 1: Widget System

- [x] T001: Create `web/tsisip/js/dashboard-widgets.js`
- [x] T002: Define widget registry with IDs and labels
- [x] T003: Implement show/hide toggle logic

## Phase 2: Persistence

- [x] T004: Save to localStorage
- [x] T005: Create `web/common/save-dashboard.php` endpoint
- [x] T006: Sync to `ocp_user_preferences` table
- [x] T007: Load preferences on page init

## Phase 3: UI Integration

- [x] T008: Add customize button to dashboard
- [x] T009: Render widgets based on preferences
- [x] T010: Default layout for new users

## Phase 4: Testing

- [x] T011: Create `test-ocp-bookmarks.sh` (covers preferences)
- [x] T012: Verify localStorage round-trip
- [x] T013: Verify server sync

## Phase 5: Future (Drag-and-Drop)

- [x] T014: HTML5 drag-and-drop widget reordering
- [x] T015: Snap-to-grid layout persistence
- [x] T016: Widget configuration (refresh interval, filters)
