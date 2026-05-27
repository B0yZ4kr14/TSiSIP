# Tasks: OCP Dark Mode

## Phase 1: CSS Variables

- [ ] T001 Audit all CSS files for hardcoded colors
- [ ] T002 Add dark mode variables to `web/tsisip/css/tsisip-variables.css`
- [ ] T003 Update `web/tsisip/css/tsisip-theme.css` to use variables exclusively

## Phase 2: Theme Toggle

- [ ] T004 Create `web/tsisip/js/theme-toggle.js`
- [ ] T005 Add toggle switch to `web/common/header.php`
- [ ] T006 Create `web/common/set-theme.php` AJAX endpoint
- [ ] T007 Update `web/common/config.php` to load theme preference

## Phase 3: System Preference

- [ ] T008 Add `prefers-color-scheme` detection to theme-toggle.js
- [ ] T009 Ensure system preference respected when no user preference set

## Phase 4: Persistence

- [ ] T010 Save preference to localStorage
- [ ] T011 Save preference to PHP session via AJAX
- [ ] T012 Restore preference on page load

## Phase 5: Accessibility

- [ ] T013 Run axe-core contrast audit on dark mode
- [ ] T014 Fix any contrast violations
- [ ] T015 Verify focus indicators visible in dark mode

## Phase 6: Validation

- [ ] T016 Visual inspection of all pages in dark mode
- [ ] T017 Test theme persistence across sessions
- [ ] T018 Test system preference detection
- [ ] T019 Update spec status to Complete
