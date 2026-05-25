# Blueprint — TSiSIP Control Panel Rebranding & Modernization

## Overview

Transform the OCP v9 user interface into a TSiSIP-branded, high-quality operational portal with a cohesive visual system (logo, palette, typography, iconography), D3.js interactive visualizations, responsive layouts, and full i18n localization — without altering underlying PHP business logic.

## Requirements

- **FR-002-001**: TSiSIP Corporate Identity System — "TSiSIP" exact casing across all touchpoints; responsive logo (full lockup desktop, compact icon mobile).
- **FR-002-002**: Color Palette & Theming — cold-tone metallic blue system; WCAG 2.1 AA compliant contrast.
- **FR-002-003**: Typography & Visual Hierarchy — primary typeface for headings, secondary for data tables; font-display swap; no render-blocking.
- **FR-002-004**: Asset Optimization & Delivery — SVG icons preferred; CSS override layer ≤150KB uncompressed; all assets local (no CDN).
- **FR-002-005**: Interactive Visualization Integration — D3.js v7 ES module charts for SIP metrics; isolated scope; graceful degradation.
- **FR-002-006**: Responsive Behavior — sidebar collapses below 1024px; tables horizontal scroll + sticky first column on mobile; forms reflow below 768px.
- **FR-002-007**: Legacy Compatibility — only `web/common/header.php` and `web/css/main.css` modified; original OCP restorable.
- **FR-002-008**: Multi-Language Localization (i18n) — English, Spanish, Portuguese via GNU gettext `.po`/`.mo` from day one.
- **FR-002-009**: Role-Aware Information Density — read-only users see condensed navigation and suppressed action columns via CSS only.
- **FR-002-010**: Asset Versioning and Cache Strategy — immutable caching with filename hashes; manifest-based lookup.

## Architecture

- **Container**: OCP v9 built from `php:8.2-apache-bookworm`; attaches to `sip_internal` and `db_internal`.
- **Frontend Stack**: CSS Custom Properties, inline SVG, D3.js v7 (ES Module), jQuery 3.x (existing, unmodified), GNU gettext.
- **Asset Pipeline**: Node.js build script reads `theme.json`, generates `tsisip-variables.css`, compiles `tsisip-theme.css`, optimizes SVGs with `svgo`, hashes filenames, produces `asset-manifest.json`, compiles `.po` to `.mo` via `msgfmt`.
- **Injection Points**: `web/common/header.php` reads manifest and emits hashed CSS links; `web/css/main.css` imports variables.
- **Output**: `web/tsisip/assets/` (generated at build time, gitignored).

## Implementation Plan

### Phase 1: Design Specification & Asset Prototyping
- `design/logo/` — full and compact SVG logo variants.
- `design/palette.md` — exact hex values with contrast documentation.
- `design/typography.md` — font stacks, scale definitions.
- `design/icons/` — optimized SVG icon set.
- `design/mockups/` — static prototypes at 1920px, 768px, 375px.

### Phase 2: Theme Engine & Asset Pipeline
- `build/generate-css-variables.js`, `build/generate-manifest.js`, `build/theme.json`.
- `web/tsisip/css/tsisip-variables.css`, `web/tsisip/css/tsisip-theme.css`.
- `web/tsisip/js/tsisip-charts.js` — D3.js ES module chart initialization.

### Phase 3: OCP Integration & Injection Points
- Modify `web/common/header.php` to load hashed assets and set `data-tsisip-role`.
- Modify `web/css/main.css` to import variables.
- `web/tsisip/locale/` — `.po`/`.mo` files for EN/ES/PT.

### Phase 4: D3.js Chart Integration
- Dispatcher Load Chart, RTPengine Session Chart, Subscriber Growth Chart, Auth Audit Heatmap.

### Phase 5: i18n Localization
- `tsisip-en.po`, `tsisip-es.po`, `tsisip-pt.po`; compiled `.mo` files.

### Phase 6: Responsive Layout & Role Density
- Responsive sidebar, data tables, form reflow, touch targets, logo swap.

### Phase 7: Validation, Performance & Hardening
- Visual regression, axe-core accessibility audit, Lighthouse CI, cross-browser, security scan.

## Tasks

**Phase 1 — Setup**
- T001: Create TSiSIP theme directory structure
- T002: Initialize asset build pipeline configuration
- T003: Create `theme.json` design token source

**Phase 2 — Foundational**
- T004: Generate CSS custom properties from `theme.json`
- T005: Create TSiSIP logo SVG assets
- T006: Build core theme CSS override layer
- T007: Create asset manifest generator
- T008: Integrate asset manifest into OCP `header.php`

**Phase 3 — US1: Administrator Branding & Navigation**
- T009: Replace OCP logo with TSiSIP responsive logo in header
- T010: Update header title and meta tags
- T011: Style Subscriber module tables for readability
- T012: Implement responsive navigation sidebar
- T013: Apply TSiSIP branding to login page

**Phase 4 — US2: NOC Operator Charts & Visualization**
- T014: Create D3.js chart initialization module
- T015: Implement Dispatcher Load Chart
- T016: Implement RTPengine Session Chart
- T017: Validate D3.js + jQuery coexistence

**Phase 5 — US3: DevOps Forms & Role Density**
- T018: Implement role-aware CSS density rules
- T019: Style Tenant Management and Header Routing forms
- T020: Set `data-tsisip-role` attribute on body

**Phase 6 — i18n Localization**
- T021: Create TSiSIP English PO source file
- T022: Create TSiSIP Spanish PO translation
- T023: Create TSiSIP Portuguese PO translation
- T024: Integrate locale files with OCP gettext domain

**Phase 7 — Polish & Validation**
- T025: Run CSS specificity audit
- T026: Run accessibility audit (axe-core)
- T027: Run performance audit (Lighthouse)
- T028: Run visual regression baseline
- T029: Validate rollback safety
- T030: Final documentation and sign-off

## Validation

- Build succeeds from clean checkout in <30 seconds.
- Total uncompressed asset payload ≤150KB (excluding fonts).
- CSS specificity audit: <20% `!important` usage.
- `git diff web/` shows changes only in `common/header.php` and `css/main.css`.
- D3.js charts render in <2s for 500 points without jQuery event conflicts.
- Lighthouse mobile audit: Performance ≥90, Accessibility 100.
- CLS during logo swap <0.05.
- 100% string coverage across EN, ES, PT.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| jQuery event handlers conflict with D3.js DOM manipulation | Isolate D3 in `<script type="module">`; run 100-cycle interaction test |
| Inline styles in third-party OCP modules override TSiSIP theme | Audit top 5 modules; provide safe override rules |
| Font loading delays cause FOUC | Use `font-display: swap`; preload critical font subset |
| Logo aspect ratio causes layout shift | Define explicit width/height; reserve space with CSS `aspect-ratio` |
| D3.js chart renders empty on malformed MI data | Implement empty-state and error-state visual components |

**Dependencies**: OCP v9 baseline; OpenSIPS 3.6 MI/FIFO metric endpoints; TSiSIP brand guidelines; GNU gettext tooling.
