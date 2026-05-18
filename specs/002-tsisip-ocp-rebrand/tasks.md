# Tasks: TSiSIP OpenSIPS Control Panel Rebranding & Modernization

## Overview

This task list translates the implementation plan into actionable, dependency-ordered work items. Tasks are organized by user story to enable independent implementation and testing.

---

## Phase 1 -- Setup

### [X] T001 Create TSiSIP theme directory structure
**Description**: Create the directory scaffold under `web/tsisip/` with subdirectories: `assets/`, `css/`, `js/`, `locale/`, `icons/`, `fonts/`. Add `.gitkeep` files where needed. Ensure the directory is gitignored as a build output if assets are generated at build time.
**Phase**: 1
**Depends on**: --
**Parallel**: [P]
**Acceptance**: Directory tree exists and matches the plan structure.

### [X] T002 Initialize asset build pipeline configuration
**Description**: Create `build/package.json` (or `build/Makefile`) defining the asset pipeline dependencies: a CSS processor (PostCSS or Sass), SVG optimizer (svgo), file-hash generator, and `msgfmt` wrapper. Include `build/` in `.gitignore` if using Node.js `node_modules`.
**Phase**: 1
**Depends on**: T001
**Parallel**: No
**Acceptance**: `npm install` (or equivalent) in `build/` succeeds without errors.

### [X] T003 Create theme.json design token source
**Description**: Create `build/theme.json` with all design tokens from `data-model.md` Entity: Theme Configuration (colors, typography, breakpoints, asset version placeholder). This is the single source of truth for the visual system.
**Phase**: 1
**Depends on**: T002
**Parallel**: No
**Acceptance**: `theme.json` parses as valid JSON and contains all required token categories.

---

## Phase 2 -- Foundational

### [X] T004 Generate CSS custom properties from theme.json
**Description**: Create `build/generate-css-variables.js` (or shell script) that reads `theme.json` and outputs `web/tsisip/css/tsisip-variables.css` with `:root { --tsisip-primary: #1A3A5C; ... }`. Ensure all color values are preserved exactly.
**Phase**: 2
**Depends on**: T003
**Parallel**: [P] with T005
**Acceptance**: Generated CSS file contains all variables from `theme.json` and loads without syntax errors in a browser.

### [X] T005 Create TSiSIP logo SVG assets
**Description**: Create `design/logo/tsisip-logo-full.svg` (horizontal lockup with "TSiSIP" text) and `design/logo/tsisip-logo-compact.svg` (icon-only variant). Both must render the name in exact casing. Add a monochrome fallback variant for each. Optimize with `svgo` and copy to `web/tsisip/assets/` with content-hash filenames.
**Phase**: 2
**Depends on**: T003
**Parallel**: [P] with T004
**Acceptance**: Both SVGs render correctly at 200px and 48px widths. Text reads "TSiSIP" in exact casing.

### [X] T006 Build core theme CSS override layer
**Description**: Create `build/tsisip-theme.src.css` (or Sass entry) that imports `tsisip-variables.css` and defines: global resets, header chrome, navigation sidebar, button styles, form styles, table styles, badge styles, and responsive breakpoints. Output to `web/tsisip/css/tsisip-theme.css` with hashed filename. Must not exceed 100KB uncompressed.
**Phase**: 2
**Depends on**: T004, T005
**Parallel**: No
**Acceptance**: CSS file loads after OCP `main.css` and successfully overrides generic OCP styles on a test page.

### [X] T007 Create asset manifest generator
**Description**: Implement a build step that scans `web/tsisip/assets/` and `web/tsisip/css/` and generates `web/tsisip/asset-manifest.json` mapping logical names (e.g., `tsisip-theme.css`) to hashed physical filenames (e.g., `tsisip-theme.a3f7c2.css`).
**Phase**: 2
**Depends on**: T006
**Parallel**: No
**Acceptance**: Manifest JSON is valid and contains entries for all generated assets.

### [X] T008 Integrate asset manifest into OCP header.php
**Description**: Modify `web/common/header.php` to read `web/tsisip/asset-manifest.json`, parse it with `json_decode`, and emit `<link rel="stylesheet">` tags for `tsisip-variables.css` and `tsisip-theme.css` using their hashed filenames. Ensure graceful fallback if the manifest is missing (do not fatal).
**Phase**: 2
**Depends on**: T007
**Parallel**: No
**Acceptance**: Rendered HTML contains correct `<link>` tags with hashed filenames; page does not fatal if manifest is absent.

---

## Phase 3 -- US1: Administrator Branding & Navigation

### [X] T009 [US1] Replace OCP logo with TSiSIP responsive logo in header
**Description**: In `web/common/header.php`, replace the generic OCP logo `<img>` or `<div>` with TSiSIP logo markup that switches between `logo-full` (>=768px) and `logo-compact` (<768px) using CSS media queries or PHP viewport detection. Reserve exact dimensions via `width`/`height` attributes to prevent layout shift.
**Phase**: 3
**Depends on**: T008
**Parallel**: [P] with T010
**Acceptance**: Logo renders correctly at desktop and mobile widths. CLS measurement <0.05.

### [X] T010 [US1] Update header title and meta tags
**Description**: In `web/common/header.php`, replace generic "OpenSIPS Control Panel" `<title>` text with "TSiSIP -- OpenSIPS Control Panel" (or localized equivalent via gettext). Add `<meta name="theme-color" content="#1A3A5C">`. Ensure browser tab and mobile chrome reflect TSiSIP branding.
**Phase**: 3
**Depends on**: T008
**Parallel**: [P] with T009
**Acceptance**: Browser tab displays TSiSIP-branded title; mobile theme-color meta is present.

### [X] T011 [US1] Style Subscriber module tables for readability
**Description**: Add CSS rules in `tsisip-theme.css` for `.dataTable` or equivalent OCP table classes: zebra striping with TSiSIP surface colors, hover states, compact padding for dense SIP data, and monospaced font for IP/HA1 columns. Ensure tables remain readable at 1366x768 without horizontal scroll on standard column sets.
**Phase**: 3
**Depends on**: T009, T010
**Parallel**: [P] with T012
**Acceptance**: Subscriber table renders with TSiSIP styling; no horizontal scroll at 1366x768 on default columns.

### [X] T012 [US1] Implement responsive navigation sidebar
**Description**: Add CSS and minimal JS (inside `tsisip-theme.css` or a small `tsisip-nav.js`) to collapse the OCP sidebar to a hamburger menu below 1024px. Ensure the collapsed state is keyboard-accessible and respects the TSiSIP color palette.
**Phase**: 3
**Depends on**: T009, T010
**Parallel**: [P] with T011
**Acceptance**: Sidebar collapses below 1024px; hamburger menu is keyboard-navigable; expands/collapses without page reload.

### [X] T013 [US1] Apply TSiSIP branding to login page
**Description**: Style the OCP login view (`web/login.php` or equivalent) with TSiSIP logo, cold-tone background, and polished form styling. Do not modify PHP auth logic. All styling must be achievable via CSS selectors targeting the login page body class.
**Phase**: 3
**Depends on**: T011, T012
**Parallel**: No
**Acceptance**: Login page displays TSiSIP logo, branded colors, and styled form elements. Authentication still functions.

---

## Phase 4 -- US2: NOC Operator Charts & Visualization

### [X] T014 [US2] Create D3.js chart initialization module
**Description**: Create `web/tsisip/js/tsisip-charts.js` as an ES module. Export an `initChart(config)` function that accepts a Chart Configuration object (from `data-model.md`), creates a D3.js SVG inside the designated container, and binds to the TSiSIP color palette via CSS variables. Include empty-state and error-state renderers.
**Phase**: 4
**Depends on**: T008
**Parallel**: [P] with T015
**Acceptance**: Module loads as `<script type="module">` without global namespace pollution; `initChart` is callable.

### [X] T015 [US2] Implement Dispatcher Load Chart
**Description**: Inject a D3.js bar/line chart into the Dispatcher module view showing target weights and health states. Container selector: `#tsisip-chart--dispatcher-load`. Data endpoint: OpenSIPS MI `get_statistics load` or equivalent. Color scale bound to `--tsisip-primary`, `--tsisip-accent-success`, `--tsisip-accent-error`.
**Phase**: 4
**Depends on**: T014
**Parallel**: [P] with T016
**Acceptance**: Chart renders within 2 seconds for <=500 data points. Respects active language preference.

### [X] T016 [US2] Implement RTPengine Session Chart
**Description**: Inject a D3.js gauge/donut chart into the RTPengine module view showing active sessions vs. capacity. Container selector: `#tsisip-chart--rtpengine-sessions`. Data endpoint: OpenSIPS MI `rtpengine_show` or equivalent.
**Phase**: 4
**Depends on**: T014
**Parallel**: [P] with T015
**Acceptance**: Chart renders with TSiSIP colors; degrades to empty-state when MI data is null.

### [X] T017 [US2] Validate D3.js + jQuery coexistence
**Description**: Run the falsification test: inject a prototype chart into the Subscriber list view (the busiest OCP view). Perform 100 automated interaction cycles (click chart bars, hover tooltips, toggle visibility). Verify no jQuery event misfires (sortable tables, modals, AJAX reloads remain functional).
**Phase**: 4
**Depends on**: T015, T016
**Parallel**: No
**Acceptance**: All 100 interaction cycles complete without jQuery event errors; surrounding PHP view remains functional.

---

## Phase 5 -- US3: DevOps Forms & Role Density

### [X] T018 [US3] Implement role-aware CSS density rules
**Description**: Add CSS selectors in `tsisip-theme.css` that suppress action columns and non-actionable navigation items for read-only users using `[data-tsisip-role="readonly"]` attribute on `<body>`. Target modules: Subscriber, Dispatcher, Header Routing Rules. No PHP modification.
**Phase**: 5
**Depends on**: T008
**Parallel**: [P] with T019
**Acceptance**: Read-only user sees no edit/delete buttons; admin user sees all buttons on identical viewport.

### [X] T019 [US3] Style Tenant Management and Header Routing forms
**Description**: Add CSS rules for form layouts in the Tenant Management and Header Routing Rules modules: polished focus states, consistent spacing, branded buttons, and modal dialogs. Ensure forms reflow from multi-column to single-column below 768px.
**Phase**: 5
**Depends on**: T008
**Parallel**: [P] with T018
**Acceptance**: Form elements display TSiSIP styling; layout stacks correctly at 375px viewport.

### [X] T020 [US3] Set data-tsisip-role attribute on body
**Description**: In `web/common/header.php`, add `data-tsisip-role="<?php echo $_SESSION['user_role']; ?>"` to the `<body>` tag. Ensure the attribute is sanitized (whitelist: `admin`, `readonly`, `user`).
**Phase**: 5
**Depends on**: T018
**Parallel**: No
**Acceptance**: Rendered HTML contains `data-tsisip-role` with valid role value; invalid roles default to `readonly`.

---

## Phase 6 -- i18n Localization

### [X] T021 Create TSiSIP English PO source file
**Description**: Create `web/tsisip/locale/tsisip-en.po` with all TSiSIP-branded strings as msgids. Include: logo alt text, page titles, chart labels (axis, tooltip, legend), empty-state messages, error-state messages, button labels, aria-labels. This is the master source for translators.
**Phase**: 6
**Depends on**: T008
**Parallel**: [P] with T022, T023
**Acceptance**: `.po` file parses without errors; contains >=30 branded strings.

### [X] T022 Create TSiSIP Spanish PO translation
**Description**: Create `web/tsisip/locale/tsisip-es.po` by translating all msgids from `tsisip-en.po` into Spanish (`es_ES`). Ensure technical terms (SIP, RTP, HA1) remain untranslated where industry convention dictates.
**Phase**: 6
**Depends on**: T021
**Parallel**: [P] with T023
**Acceptance**: `msgfmt` compiles without errors; translation count matches English source.

### [X] T023 Create TSiSIP Portuguese PO translation
**Description**: Create `web/tsisip/locale/tsisip-pt.po` by translating all msgids from `tsisip-en.po` into Portuguese (`pt_BR`). Ensure technical terms follow Brazilian telecom conventions.
**Phase**: 6
**Depends on**: T021
**Parallel**: [P] with T022
**Acceptance**: `msgfmt` compiles without errors; translation count matches English source.

### [X] T024 Integrate locale files with OCP gettext domain
**Description**: Ensure OCP's PHP `bindtextdomain` configuration includes the `tsisip` domain pointing to `web/tsisip/locale/`. Verify that switching OCP language preference dynamically loads the correct `.mo` file for branded strings.
**Phase**: 6
**Depends on**: T022, T023
**Parallel**: No
**Acceptance**: Changing OCP language to ES or PT updates all TSiSIP-branded text without restart.

---

## Phase 7 -- Polish & Validation

### [X] T025 Run CSS specificity audit
**Description**: Audit the top 10 most-accessed OCP views with TSiSIP theme applied. Count branded elements and identify how many require `!important` to override OCP defaults. If >20%, pivot to per-module stylesheet compilation as documented in the falsification hypothesis.
**Phase**: 7
**Depends on**: T013, T017, T019, T024
**Parallel**: [P] with T026
**Acceptance**: Audit report documents specificity count per view; `!important` usage is <=20%.

### [X] T026 Run accessibility audit (axe-core)
**Description**: Run automated axe-core scans on primary user paths: login, subscriber list, dispatcher dashboard, tenant management. Fix any WCAG 2.1 AA violations (contrast, missing labels, keyboard navigation gaps).
**Phase**: 7
**Depends on**: T013, T017, T019, T024
**Parallel**: [P] with T025
**Acceptance**: Zero WCAG 2.1 AA violations on all scanned paths.

### [X] T027 Run performance audit (Lighthouse)
**Description**: Run Lighthouse CI on the OCP dashboard view at 375px and 1920px viewports. Enforce budgets: FCP <1.0s, LCP <1.5s, TTI <2.0s, Total asset payload <150KB.
**Phase**: 7
**Depends on**: T025, T026
**Parallel**: [P] with T028
**Acceptance**: Lighthouse scores: Performance >=90, Accessibility 100.

### [X] T028 Run visual regression baseline
**Description**: Capture baseline screenshots of the top 10 OCP views with TSiSIP theme applied. Use BackstopJS or similar. Exclude dynamic content (timestamps, session IDs) from diff comparison.
**Phase**: 7
**Depends on**: T025, T026
**Parallel**: [P] with T027
**Acceptance**: Baseline screenshots are stored under `tests/visual-regression/` and can be re-run for regression detection.

### [X] T029 Validate rollback safety
**Description**: Remove the `web/tsisip/` directory and revert `web/common/header.php` and `web/css/main.css` to their original OCP v9 state. Verify that the OCP interface returns to its original generic appearance and all functionality remains intact.
**Phase**: 7
**Depends on**: T027, T028
**Parallel**: No
**Acceptance**: Original OCP branding is fully restored; jQuery interactions, tables, modals work identically.

### [X] T030 Final documentation and sign-off
**Description**: Update `AGENTS.md` with build/test commands for the OCP rebranding layer. Create `specs/002-tsisip-ocp-rebrand/README.md` with operator instructions for building assets, running audits, and rebasing onto future OCP releases. Validate that all 10 Success Criteria are traceable to validation steps.
**Phase**: 7
**Depends on**: T029
**Parallel**: No
**Acceptance**: Documentation is accurate; all SCs have corresponding validation steps in Phases 3-7.

---

## Dependency Graph

Phase 1 (Setup)
    |
    v
Phase 2 (Foundational)
    |
    +---> Phase 3 (US1: Administrator Branding)
    |         |
    |         v
    +---> Phase 4 (US2: NOC Operator Charts)
    |         |
    |         v
    +---> Phase 5 (US3: DevOps Forms & Role Density)
    |         |
    |         v
    +---> Phase 6 (i18n Localization)
              |
              v
         Phase 7 (Polish & Validation)

Phases 3, 4, and 5 can begin in parallel once Phase 2 completes, because each user story builds on the shared theme foundation but does not depend on the others. Phase 6 (i18n) depends on all UI strings being finalized, so it follows Phases 3-5. Phase 7 validates everything.

---

## Parallel Execution Opportunities

| Story | Parallel Tasks | Files |
|---|---|---|
| Setup | T001 + T002 | Different directories |
| Foundational | T004 + T005 | CSS generator + SVG design |
| US1 | T009 + T010 | Header logo + meta tags |
| US1 | T011 + T012 | Table styling + nav sidebar |
| US2 | T014 + T015 + T016 | Chart module + individual charts |
| US3 | T018 + T019 | Role CSS + form styling |
| i18n | T022 + T023 | ES and PT translations |
| Polish | T025 + T026 + T027 + T028 | Audit suites run independently |

---

## MVP Scope

The recommended Minimum Viable Product (MVP) delivers **US1: Administrator Branding & Navigation** only:
- Setup + Foundational phases (T001-T008)
- US1 tasks (T009-T013)
- Rollback validation (T029)

This gives a fully branded, responsive OCP interface for administrators. US2 (charts), US3 (role density), and i18n can be delivered in subsequent sprints.
