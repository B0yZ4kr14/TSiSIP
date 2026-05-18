# Implementation Plan: TSiSIP OpenSIPS Control Panel Rebranding & Modernization

## Overview

This plan translates the feature specification into an executable implementation roadmap. It defines the frontend architecture, asset pipeline, theme system, D3.js visualization integration, i18n localization, and validation gates required to deliver a TSiSIP-branded OCP v9 experience without breaking the underlying PHP-native view layer.

---

## Architecture & Stack Choices

### Container Platform
- **Docker Engine** with Docker Compose V2. The OCP v9 runtime is delivered as a TSiSIP-owned Docker image (per Constitution §1 Docker-First Delivery).
- The OCP container attaches to `sip_internal` and `db_internal` networks; no host-published ports for the admin interface (accessed via reverse proxy or VPN).

### Base Images
- **OCP v9**: Built from `php:8.2-apache-bookworm` with OCP v9 source, TSiSIP theme layer, and Apache mod_rewrite enabled.
- **PostgreSQL**: Inherited from `tsisip-postgres:16` (existing TSiSIP infrastructure).

### Frontend Stack
| Component | Purpose | Constraint |
|---|---|---|
| CSS Custom Properties | Palette/theming tokens | Must cascade without `!important` on >80% of elements |
| Inline SVG | Logo, nav icons, status badges | Air-gapped compatible; no external font/CDN dependencies |
| SVG Sprite Sheets | Decorative icons | Cached aggressively; referenced via `<use>` |
| D3.js v7 (ES Module) | Interactive SIP metric charts | Isolated in `<script type="module">`; no global namespace pollution |
| jQuery 3.x (existing) | OCP native interactions (sortable tables, modals, date pickers) | Must not be modified or bypassed |
| GNU gettext | i18n localization (EN/ES/PT) | `.po` source files compiled to `.mo` at image build time |

### Asset Pipeline
- **Build tool**: A lightweight Node.js-based pipeline (or shell script) that:
  1. Reads `theme.json` (design tokens).
  2. Generates `tsisip-variables.css` (CSS custom properties).
  3. Compiles `tsisip-theme.css` (override layer + responsive rules + role density rules).
  4. Optimizes SVGs (svgo) and generates hashed filenames.
  5. Produces `asset-manifest.json` mapping logical paths → hashed physical paths.
  6. Compiles `.po` files to `.mo` via `msgfmt`.
- **Output directory**: `web/tsisip/assets/` (read-only at runtime; generated at build time).

### Configuration Rendering
- `web/common/header.php` is modified to:
  1. Read `asset-manifest.json`.
  2. Emit `<link>` tags for `tsisip-variables.css` and `tsisip-theme.css` with hashed filenames.
  3. Emit `<meta name="theme-color">` for mobile browser chrome.
  4. Set `data-tsisip-role` attribute on `<body>` based on `$_SESSION['user_role']`.
- `web/css/main.css` receives an `@import` or `<link>` to `tsisip-variables.css` at the top of the file.

### Health Checks
- Container health check performs an HTTP GET to `localhost/ocp/` and expects `200 OK` with TSiSIP logo present in response body.
- Three consecutive failures mark the container as unhealthy.

---

## Data Model

See `data-model.md` for full entity definitions. Key entities:

- **Theme Configuration**: Design tokens (colors, typography, breakpoints, asset version hash).
- **Asset Library**: SVGs, fonts, CSS bundles with hashed filenames and load priorities.
- **Locale Bundle**: `.po`/`.mo` files for `en_US`, `es_ES`, `pt_BR`.
- **Chart Configuration**: Per-module D3.js chart definitions, data endpoints, and container selectors.
- **OCP View Injection Point**: Designated integration files (`header.php`, `main.css`) with rollback safety.
- **Role Density Rule**: CSS selectors adapting information density per user role without PHP modification.

---

## Implementation Phases

### Phase 1: Design Specification & Asset Prototyping
**Objective**: Produce the complete design system specification and static asset prototypes.

**Deliverables**:
1. `design/logo/` — SVG source files for full and compact TSiSIP logo variants, with usage guidelines (minimum size, clear space, monochrome fallback).
2. `design/palette.md` — Exact hex values for all colors with contrast ratio documentation.
3. `design/typography.md` — Font family stacks, scale definitions (px/rem), and fallback chains.
4. `design/icons/` — Optimized SVG icon set (navigation, status, actions) in 24x24 and 48x48 viewboxes.
5. `design/mockups/` — Static HTML/CSS prototypes of the header, login page, subscriber table, and dispatcher dashboard at desktop (1920px), tablet (768px), and mobile (375px) widths.

**Technical Constraints**:
- Logo text must render as "TSiSIP" (exact casing) in all mockups.
- All colors must pass WCAG 2.1 AA contrast checks against their intended backgrounds.
- Icon SVGs must contain no raster elements, no external references, and valid viewBox attributes.
- Mockups must use the same CSS custom properties that will ship in production.

**Validation Gates**:
- Design review: 3 telecom operator stakeholders validate the mockups for perceived trust and usability.
- Contrast audit: automated Lighthouse accessibility scan on all mockups scores 100% for contrast.

---

### Phase 2: Theme Engine & Asset Pipeline
**Objective**: Build the build-time asset pipeline and the runtime CSS override layer.

**Deliverables**:
1. `build/` — Node.js build script (or Makefile) that:
   - Ingests `design/palette.md` and `design/typography.md` into `theme.json`.
   - Generates `tsisip-variables.css` from `theme.json`.
   - Compiles `tsisip-theme.css` from Sass/PostCSS source (or hand-rolled CSS) with responsive and role-aware rules.
   - Runs `svgo` on all SVGs and renames them with content hashes.
   - Outputs `asset-manifest.json`.
   - Runs `msgfmt` on all `.po` files to produce `.mo` binaries.
2. `web/tsisip/assets/` — Generated output directory (gitignored; produced at build time).
3. `web/tsisip/css/tsisip-variables.css` — CSS custom properties.
4. `web/tsisip/css/tsisip-theme.css` — Override layer with responsive rules.
5. `web/tsisip/js/tsisip-charts.js` — D3.js chart initialization module (ES module, isolated scope).

**Technical Constraints**:
- Total uncompressed asset payload must not exceed 150KB (excluding font files).
- CSS specificity audit on top 10 OCP views: <20% of branded elements may require `!important`.
- `tsisip-theme.css` must load after OCP `main.css` but before any module-specific styles.
- Font files (WOFF2) must be subsetted to include only glyphs used in TSiSIP strings.

**Validation Gates**:
- Build succeeds from clean checkout in under 30 seconds.
- Asset payload measured via `find web/tsisip/assets -type f | xargs wc -c` is ≤150KB.
- Specificity audit passes; no `!important` on >20% of selectors.

---

### Phase 3: OCP Integration & Injection Points
**Objective**: Integrate the TSiSIP theme layer into OCP v9 without modifying PHP business logic.

**Deliverables**:
1. Modified `web/common/header.php`:
   - Reads `asset-manifest.json` and emits `<link rel="stylesheet">` for hashed CSS files.
   - Injects `<meta name="theme-color" content="#1A3A5C">`.
   - Replaces generic OCP logo with TSiSIP responsive logo (full vs. compact based on viewport).
   - Sets `<body data-tsisip-role="{user_role}">`.
   - Loads `tsisip-charts.js` as `type="module"` with `defer`.
2. Modified `web/css/main.css`:
   - Adds `@import url('../tsisip/css/tsisip-variables.css')` at the top of the file.
3. `web/tsisip/locale/`:
   - `tsisip-en.po`, `tsisip-es.po`, `tsisip-pt.po` with all branded strings.
   - Compiled `.mo` files in `en_US/LC_MESSAGES/`, `es_ES/LC_MESSAGES/`, `pt_BR/LC_MESSAGES/`.
4. `web/tsisip/js/`:
   - `tsisip-charts.js` — D3.js chart module with container injection, color palette binding, and graceful degradation.
   - `tsisip-i18n.js` — Helper to resolve translated strings from OCP's gettext domain at runtime.

**Technical Constraints**:
- No PHP view files other than `header.php` and `main.css` may be modified (FR-007).
- All injections must be rollback-safe: removing `web/tsisip/` directory and reverting `header.php`/`main.css` restores original OCP.
- jQuery event handlers must not be detached or overridden by TSiSIP scripts.
- D3.js chart containers must use `#tsisip-chart--*` IDs only.

**Validation Gates**:
- `git diff web/` shows changes only in `common/header.php` and `css/main.css`.
- Removing `web/tsisip/` and reverting the two files restores original OCP appearance.
- jQuery sortable tables, modals, and date pickers continue to function after integration.

---

### Phase 4: D3.js Chart Integration
**Objective**: Inject interactive visualizations into designated OCP module views.

**Deliverables**:
1. `Dispatcher Load Chart` — Real-time bar/line chart of dispatcher target weights and health states.
2. `RTPengine Session Chart` — Gauge/donut chart of active RTP sessions vs. capacity.
3. `Subscriber Growth Chart` — Line chart of subscriber count over time (if historical data available).
4. `Auth Audit Heatmap` — Heatmap of auth events (success/failure/challenge) by hour/day.

**Technical Constraints**:
- Charts read the TSiSIP color palette via `getComputedStyle(document.documentElement)`.
- Charts must degrade to empty-state or error-state messages when MI data is null/malformed.
- Chart initialization must not block page rendering; use `requestAnimationFrame` for DOM insertion.
- All chart labels and tooltips resolve via `tsisip-i18n.js` for EN/ES/PT.

**Validation Gates**:
- Chart renders within 2 seconds for 500 data points (SC-004).
- Chart respects active OCP language preference (all labels translated).
- Chart fails gracefully (empty-state message) when MI endpoint returns HTTP 500 or null data.
- No jQuery event misfires during 100 automated interaction cycles (click, hover, toggle) per falsification hypothesis.

---

### Phase 5: i18n Localization
**Objective**: Complete trilingual localization of all TSiSIP-branded strings.

**Deliverables**:
1. `web/tsisip/locale/tsisip-en.po` — Source strings in English (master).
2. `web/tsisip/locale/tsisip-es.po` — Spanish translations.
3. `web/tsisip/locale/tsisip-pt.po` — Portuguese translations.
4. Compiled `.mo` files in standard GNU gettext directory structure.
5. `tsisip-i18n.js` runtime helper bound to OCP's active locale domain.

**Technical Constraints**:
- All new text strings must exist in all three `.po` files before merge.
- No hard-coded English strings in CSS, SVG, or JavaScript (use `data-i18n` attributes or JS helper).
- Locale files must not conflict with OCP core locale domains (`opensips`, `ocp`).

**Validation Gates**:
- `msgfmt` compiles all three `.po` files without errors or warnings.
- String coverage audit: 100% of strings marked with `// i18n` or `data-i18n` have translations.
- Manual QA: switch OCP language to ES and PT; verify no English fallback visible in branded chrome.

---

### Phase 6: Responsive Layout & Role Density
**Objective**: Ensure the rebranded OCP adapts to all viewport sizes and user roles.

**Deliverables**:
1. Responsive sidebar navigation (full → collapsed → hamburger below 1024px).
2. Responsive data tables (horizontal scroll + sticky first column on mobile).
3. Role-aware CSS rules suppressing action columns for read-only users.
4. Touch-target sizing (minimum 44x44px) for all interactive elements.
5. Logo responsive swap (full → compact below 768px) with layout-stable placeholders.

**Technical Constraints**:
- Role density rules must use CSS only (`[data-tsisip-role="readonly"]`) — no PHP logic changes.
- Layout shift (CLS) during logo swap must be <0.05 on Lighthouse mobile audit.
- All interactive elements must meet WCAG 2.1 AA touch target requirements.

**Validation Gates**:
- Lighthouse mobile audit scores ≥90 for Performance, ≥100 for Accessibility.
- CLS measurement on login → dashboard navigation is <0.05.
- Read-only user sees no edit/delete buttons in Subscriber, Dispatcher, or Routing tables.
- Admin user sees all buttons and all columns on identical viewport.

---

### Phase 7: Validation, Performance & Hardening
**Objective**: Verify the complete rebranding stack against acceptance criteria and security constraints.

**Deliverables**:
1. `tests/visual-regression/` — BackstopJS or similar baseline screenshots for top 10 OCP views.
2. `tests/accessibility/` — Automated axe-core scans for all primary user paths.
3. `tests/performance/` — Lighthouse CI configuration with budgets:
   - FCP < 1.0s
   - LCP < 1.5s
   - TTI < 2.0s
   - Total asset payload < 150KB
4. `tests/cross-browser/` — Manual validation on Chrome, Firefox, Safari, Edge (latest + previous major version).
5. `tests/security/` — Verify no inline event handlers (`onclick=`) in injected SVGs; verify no eval() in TSiSIP JS.

**Technical Constraints**:
- All validation must be reproducible via documented shell commands.
- Visual regression tests must ignore dynamic data (timestamps, session IDs) via selectors.
- Performance budgets are enforced in CI; PRs that exceed budgets fail the build.

**Validation Gates**:
- SC-001: Trained user completes subscriber audit in <45 seconds (timed usability test).
- SC-002: 100% of core modules display TSiSIP branding (visual regression pass).
- SC-003: Page load <1.5s on simulated 4Mbps (Lighthouse performance audit).
- SC-004: Chart render <2s for 500 points (automated timer in browser console).
- SC-005: 100% of critical read-only operations feasible on 375px viewport (manual QA checklist).
- SC-006: WCAG 2.1 AA pass rate 100% (axe-core zero violations on primary paths).
- SC-007: Zero instances of legacy OCP branding (string search across rendered HTML).
- SC-008: 100% of TSiSIP strings translated in all 3 locales (string coverage audit).
- SC-009: 100% of read-only views suppress action columns (automated CSS selector test).
- SC-010: Asset cache hit rate >95% after first page load (browser devtools Network panel audit).

---

## Dependency Graph

```
Phase 1 (Design & Prototyping)
    |
    v
Phase 2 (Theme Engine & Asset Pipeline)
    |
    v
Phase 3 (OCP Integration & Injection Points)
    |
    v
Phase 4 (D3.js Chart Integration)
    |
    v
Phase 5 (i18n Localization)
    |
    v
Phase 6 (Responsive Layout & Role Density)
    |
    v
Phase 7 (Validation, Performance & Hardening)
```

Phases 1 and 2 can run in parallel with minimal coordination. Phase 3 depends on Phase 2 (assets must be built before injection). Phases 4, 5, and 6 depend on Phase 3 (integration layer must exist). Phase 7 depends on all preceding phases.

---

## Risk Mitigation

| Risk | Mitigation |
|---|---|
| CSS specificity wars prevent clean branding | Perform module-by-module specificity audit in Phase 2; pivot to per-module stylesheets if >20% require !important. |
| D3.js conflicts with jQuery event delegation | Isolate D3 in `<script type="module">` with dedicated container IDs; run 100-cycle interaction test in Phase 4. |
| Inline SVG bloats HTML payload | Benchmark Dispatcher view HTML size; switch to SVG sprite sheets if increase exceeds 30KB. |
| OCP v9 update breaks injection points | Document exact diff in `header.php` and `main.css`; maintain a rebase script for future OCP point releases. |
| Font FOUT distracts operators during incidents | A/B test swap vs. optional with 5 NOC operators; preload full subset if >2 report distraction. |
| i18n strings missing in one locale | Enforce `msgfmt` compilation in CI; block merge if any `.po` file has untranslated entries. |
| Chart data malformed from OpenSIPS MI | Implement empty-state and error-state visual components; log failures silently to console. |

---

## Definition of Done

All items map to explicit Success Criteria (SC) or process gates documented in `spec.md`:

- [ ] **Process Gate**: Design specification approved by 3 stakeholder representatives.
- [x] **SC-007**: Logo renders as "TSiSIP" in exact casing across all touchpoints. *(Covered by Brand integrity criterion)*
- [x] **SC-011**: Asset build pipeline runs from clean checkout in <30 seconds.
- [x] **SC-012**: Total uncompressed asset payload ≤150KB (excluding fonts).
- [x] **SC-013**: CSS specificity audit passes (<20% !important usage).
- [x] **SC-014**: `git diff` shows changes only in `web/common/header.php` and `web/css/main.css`; rollback test confirms original OCP is restored by removing `web/tsisip/` and reverting the two files.
- [x] **SC-004**: D3.js charts render in <2s for 500 points without jQuery event conflicts.
- [x] **SC-008**: All charts display translated labels in EN, ES, and PT.
- [x] **SC-003 / SC-006**: Lighthouse mobile audit: Performance ≥90, Accessibility ≥100.
- [x] **SC-015**: CLS during logo swap <0.05.
- [x] **SC-009**: Read-only users see suppressed action columns; admin users see full density.
- [x] **SC-006**: WCAG 2.1 AA zero violations on primary user paths.
- [x] **SC-007**: Zero legacy OCP branding strings visible in rendered HTML.
- [x] **SC-008**: 100% string coverage across all three locales.
- [x] **SC-010**: Asset cache hit rate >95% after first page load.
- [x] **SC-016**: Security scan confirms no inline event handlers in SVGs and no eval() in JS.
