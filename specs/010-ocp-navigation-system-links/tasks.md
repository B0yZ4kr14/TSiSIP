# Tasks: OCP Navigation System Links
**Last Updated**: 2026-05-19

## Phase 1 — Dashboard Restructure

### [completed] T1.1: Add System Management section to dashboard
**Description**: Update `web/dashboard.php` to show `dispatcher.php` and `rtpengine.php` links for admin/devops roles only. Separate from Documentation & Wiki section.
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: Admin curl shows "System Management" with Dispatcher and RTPengine links.

### [completed] T1.2: Add System Status indicators
**Description**: Add visual status list (OpenSIPS, RTPengine, PostgreSQL, OCP) with colored dot indicators to the dashboard.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: Dashboard HTML contains `.tsisip-status-dot--ok` elements.

## Phase 2 — Sidebar Restructure

### [completed] T2.1: Add System section to sidebar
**Description**: Update `web/common/role-nav.php` to include a "System" heading with Dispatcher and RTPengine links for admin/devops. Separate from "Wiki" heading.
**Phase**: 2
**Depends on**: T1.1
**Parallel**: [P] with T2.2
**Acceptance**: Sidebar HTML contains "System" heading and `dispatcher.php` link for Admin.

### [completed] T2.2: Fix active-state highlighting for system pages
**Description**: Ensure `$currentPage` detection marks `dispatcher.php` and `rtpengine.php` as active correctly, independent of wiki page highlighting.
**Phase**: 2
**Depends on**: T1.1
**Parallel**: [P] with T2.1
**Acceptance**: Curl on `/dispatcher.php` shows `is-active` class on the Dispatcher sidebar item.

## Phase 3 — Styling

### [completed] T3.1: Add CSS for nav headings
**Description**: Append `.tsisip-nav-heading` styles to `web/tsisip/css/tsisip-theme.css` for visual separation between System and Wiki sections.
**Phase**: 3
**Depends on**: T2.1
**Parallel**: [P] with T3.2
**Acceptance**: CSS file contains `.tsisip-nav-heading` rules.

### [completed] T3.2: Add CSS for status dots
**Description**: Append `.tsisip-status-dot` and `.tsisip-status-dot--ok` styles to theme CSS.
**Phase**: 3
**Depends on**: T1.2
**Parallel**: [P] with T3.1
**Acceptance**: CSS file contains `.tsisip-status-dot--ok` rules with green color.

## Phase 4 — Validation & Deploy

### [completed] T4.1: PHP syntax validation
**Description**: Run `php -l` on all modified PHP files.
**Phase**: 4
**Depends on**: T1.1, T2.1
**Parallel**: No
**Acceptance**: All PHP files report "No syntax errors".

### [completed] T4.2: Local Docker validation
**Description**: Build OCP image, start container, login as Admin, verify dashboard shows system links.
**Phase**: 4
**Depends on**: T4.1
**Parallel**: No
**Acceptance**: Curl shows Dispatcher and RTPengine links in dashboard HTML.

### [completed] T4.3: VPS deploy and verification
**Description**: Push image to GHCR, deploy on VPS, login as Admin, verify system links visible.
**Phase**: 4
**Depends on**: T4.2
**Parallel**: No
**Acceptance**: VPS curl shows identical results to local validation.

### [completed] T4.4: Update canonical docs
**Description**: Update `docs/TSiSIP-CANONICAL-SPEC.md` section 19.1 to describe the dashboard sections.
**Phase**: 4
**Depends on**: T4.3
**Parallel**: No
**Acceptance**: Docs mention System Management and Documentation & Wiki sections.
