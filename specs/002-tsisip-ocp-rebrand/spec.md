# Feature Specification: TSiSIP Control Panel Rebranding & Modernization

## Overview

**Feature**: TSiSIP Control Panel Rebranding & Modernization
**Short name**: tsisip-ocp-rebrand
**Created**: 2026-05-17
**Status**: Implemented
**Last Updated**: 2026-05-19

### Context

OCP v9 is the upstream web management interface adapted by this project as the TSiSIP Control Panel. Within the TSiSIP ecosystem, it serves as the primary operational cockpit for SIP administrators, NOC operators, and tenant managers. Currently, the upstream OCP ships with a generic, unbranded visual identity that does not reflect the quality positioning of the TSiSIP platform. This feature delivers a complete visual and experiential rebranding of OCP v9 under the TSiSIP corporate identity, elevating perceived quality without altering the underlying PHP-native view architecture or breaking existing administrative workflows.

### Objective

Transform the OCP v9 user interface into a TSiSIP-branded, high-quality operational portal that:
- Communicates trust, technical excellence, and enterprise-grade reliability to telecom operators
- Provides a cohesive visual system (logo, palette, typography, iconography) applied consistently across all OCP views
- Maintains full functional compatibility with existing OCP v9 PHP-native views, modules, and plugin ecosystem
- Introduces interactive data visualization capabilities for SIP metrics and trunk analytics without conflicting with legacy jQuery-based DOM manipulation
- Remains fully responsive across desktop workstations, tablet-based NOC dashboards, and mobile emergency access scenarios

---

## Clarifications

### Session 2026-05-17

- **Q1**: What is the minimum acceptable security posture for injected SVG and D3.js assets? → **A**: No explicit security layer beyond standard OCP v9 auth; trust all local assets implicitly (Option A). The TSiSIP rebranding layer inherits the existing OCP authentication and session management model without introducing additional CSP, SRI, or server-side SVG sanitization.
- **Q2**: Must the rebranding support localization beyond English? → **A**: Full i18n from day one; translate all new strings into English, Spanish, and Portuguese (Option C). All branding strings, chart labels, empty-state messages, and accessibility labels must be externalized into OCP-compatible `.po`/`.mo` locale files for these three languages.
- **Q3**: Should the rebranded interface vary visually or informationally by user role? → **A**: Uniform visual identity across all roles, but role-aware information density (Option B). Admins see full navigation and data density; read-only users see simplified header and condensed table views with fewer action columns.
- **Q4**: What is the expected peak concurrency and caching strategy for rebranding assets? → **A**: Target 50 concurrent authenticated sessions with immutable, versioned asset caching and filename-hash cache busting (Option B). All TSiSIP assets are served with `Cache-Control: public, max-age=31536000, immutable` and versioned via filename hash or query-string for instant cache invalidation on theme updates.

---

## User Scenarios & Testing

### Primary Flows

#### Scenario 1: Administrator performs daily subscriber audit
- **Given** a SIP administrator with full write access has authenticated into the OCP through the TSiSIP-branded login portal
- **When** the administrator navigates to the Subscriber module, searches for a domain, and reviews credential health
- **Then** the interface presents TSiSIP-branded chrome, full navigation, all data columns, edit/delete action buttons, and responsive tables that remain readable on a 1920x1080 NOC monitor and a 1366x768 laptop without horizontal scroll

#### Scenario 2: NOC operator monitors real-time trunk health
- **Given** a read-only NOC operator is viewing the Dispatcher or RTPengine status page during peak traffic
- **When** the operator toggles between tabular data and an interactive visualization of session distribution
- **Then** the D3.js-powered chart renders within 2 seconds, action columns are suppressed, the navigation sidebar shows only monitoring modules, and the TSiSIP color palette is respected without triggering JavaScript conflicts with existing OCP UI behaviors

#### Scenario 3: DevOps engineer deploys a new tenant environment
- **Given** a DevOps engineer is provisioning a new TSiSIP tenant using the OCP
- **When** the engineer accesses the Tenant Management and Header Routing Rules modules
- **Then** all form elements, modals, confirmation dialogs, and notification toasts display the TSiSIP visual system consistently, including logo placement in the header and polished hover/focus states

### Edge Cases & Error Conditions

- **Edge case 1**: A user accesses the OCP from a legacy browser or an air-gapped environment with no internet access. All TSiSIP-branded assets (fonts, icons, CSS) must load from local project files; no external CDN dependencies may block rendering.
- **Edge case 2**: An OCP module contains inline styles or hard-coded color values in third-party PHP views. The rebranding system must gracefully override or coexist without manual per-file patching.
- **Edge case 3**: A D3.js visualization receives malformed or null metric data from the OpenSIPS MI/FIFO interface. The chart must degrade to an empty-state message in TSiSIP branding rather than throwing an unhandled exception that breaks the surrounding PHP page.

---

## Functional Requirements

### FR-001: TSiSIP Corporate Identity System
**Description**: Establish and enforce a unified visual identity system that replaces all generic OCP branding with TSiSIP corporate assets.
**Acceptance Criteria**:
- The corporate name "TSiSIP" appears in exact casing (all uppercase except the central lowercase "i") in all branded touchpoints: header title, login page, browser tab, and email templates
- A new logo specification is created and deployed, replacing the generic OCP logo in `web/common/header.php` and the login view
- Logo behavior is responsive: full horizontal lockup on desktop, compact icon-only variant on mobile viewports below 768px
- No generic upstream "OpenSIPS Control Panel" strings remain visible to authenticated users

### FR-002: Color Palette & Theming
**Description**: Define and apply a cold-tone color system that conveys metallic blue authority, technical precision, and enterprise trust.
**Acceptance Criteria**:
- A primary palette is specified with a metallic blue anchor, supported by slate, gunmetal, and ice gradients
- Accent colors for success, warning, error, and info states are harmonized with the cold-tone family rather than generic bootstrap defaults
- All color definitions are centralized in a theme configuration that cascades to tables, forms, buttons, navigation, badges, and charts
- Sufficient contrast ratios are maintained for WCAG 2.1 AA compliance across all text-background combinations

### FR-003: Typography & Visual Hierarchy
**Description**: Implement a typographic system that improves readability of dense SIP data while reinforcing brand character.
**Acceptance Criteria**:
- A primary typeface is specified for headings and navigation, conveying technical authority
- A secondary typeface is specified for data tables and form labels, optimizing legibility at small sizes (11–13px) for long numeric strings (IPs, ports, HA1 hashes)
- Font loading must not block initial paint; fallback system fonts must display instantly
- Typography scale is enforced consistently across headers, body, captions, and monospaced data fields

### FR-004: Asset Optimization & Delivery
**Description**: Ensure all rebranding assets (CSS, SVG icons, fonts) are optimized for fast delivery and minimal render-blocking in telecom operational environments.
**Acceptance Criteria**:
- SVG icons replace raster PNGs wherever feasible, with inline SVG preferred for critical UI chrome to eliminate HTTP requests
- CSS is structured to allow the OCP baseline to load first, with TSiSIP overrides applied as a thin thematic layer
- Total added weight of the rebranding asset layer must not exceed 150KB uncompressed (excluding optional font files)
- All assets are served from local project paths; no external CDN or tracking dependencies are introduced

### FR-005: Interactive Visualization Integration
**Description**: Enable D3.js-based interactive charts within OCP v9 views for SIP metrics, trunk load, and session analytics without breaking legacy PHP view rendering.
**Acceptance Criteria**:
- Chart containers are injected into designated OCP module views through non-invasive DOM insertion points
- D3.js initialization is isolated in its own execution context to prevent namespace collisions with existing jQuery event handlers
- Charts respect the TSiSIP color palette for data series, axes, tooltips, and legends
- Charts are responsive: they reflow within their container on window resize without page reload
- Chart initialization fails gracefully if the D3.js library or metric endpoint is unavailable, leaving the surrounding PHP view fully functional

### FR-006: Responsive Behavior Across Viewports
**Description**: Ensure the rebranded OCP remains fully operable across the device spectrum used in telecom operations.
**Acceptance Criteria**:
- Navigation sidebar collapses to a hamburger menu on viewports below 1024px
- Data tables support horizontal scroll with frozen first-column context on mobile
- Form layouts reflow from multi-column to single-column stacking below 768px
- The header logo switches to the compact icon variant below 768px without layout shift
- Touch targets (buttons, nav items) maintain a minimum 44x44px active area on tablet and mobile

### FR-007: Legacy Compatibility & Non-Breaking Override
**Description**: The rebranding must be a cosmetic layer that does not alter PHP business logic, SQL queries, or module controllers.
**Acceptance Criteria**:
- No existing OCP PHP view files are modified except for `web/css/main.css` and `web/common/header.php` as designated injection points
- Third-party OCP modules continue to render without visual regression or functional breakage
- The original OCP theme can be restored by removing or disabling the TSiSIP theme layer
- All jQuery-based interactions (sortable tables, modal dialogs, date pickers) continue to function identically

### FR-008: Multi-Language Localization (i18n)
**Description**: All TSiSIP-branded text and labels must be localized into English, Spanish, and Portuguese from day one, integrating with OCP v9's existing gettext/i18n infrastructure.
**Acceptance Criteria**:
- All new branding strings (logo alt text, page titles, chart labels, empty-state messages, button labels, aria-labels) are externalized into `.po` source files, not hard-coded in CSS or JavaScript
- Locale files follow OCP's existing directory structure and naming convention (`en_US`, `es_ES`, `pt_BR`)
- Charts dynamically load translated axis labels, tooltips, and legends based on the user's active OCP language preference
- SVG icons containing text (if any) use `<text>` elements with `data-i18n` attributes or equivalent hooks for runtime translation
- No fallback to English occurs for supported languages when translations are present

### FR-009: Role-Aware Information Density
**Description**: The TSiSIP-branded interface must adapt information density based on the authenticated user's OCP role, presenting simplified views for read-only operators while preserving full density for administrators.
**Acceptance Criteria**:
- Read-only users see a condensed navigation sidebar with non-actionable modules hidden or collapsed
- Data tables for read-only users suppress action columns (edit, delete, enable/disable) via CSS role-based selectors, without modifying PHP view logic
- Admin users retain full navigation expansion, all data columns, and all action buttons
- The visual identity (colors, logo, typography) remains identical across all roles; only information density and navigational scope differ
- Role detection leverages existing OCP session role variables; no new role-management backend is introduced

### FR-010: Asset Versioning and Cache Strategy
**Description**: All TSiSIP rebranding assets must be delivered with aggressive HTTP caching and versioned filenames to minimize repeat-download overhead across OCP page navigations while enabling instant cache invalidation on theme updates.
**Acceptance Criteria**:
- CSS, SVG, and font files are served with `Cache-Control: public, max-age=31536000, immutable` headers
- Each asset filename includes a content-hash suffix (e.g., `tsisip-main.a3f7c2.css`) or a versioned query string for cache busting
- The theme build process generates a manifest mapping logical asset names to hashed physical filenames
- On theme update, only changed assets receive new hashes; unchanged assets retain their cached copies in the browser
- Total asset payload per page remains under 150KB uncompressed after caching headers are applied

---

## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-001 | Administrator task completion efficiency | Time to locate and edit a subscriber record from login | Under 45 seconds for a trained user |
| SC-002 | Visual consistency across modules | Percentage of authenticated views displaying TSiSIP branding | 100% of core modules (Subscriber, Dispatcher, RTPengine, Tenant, Routing) |
| SC-003 | Page load performance on NOC hardware | Time from navigation click to fully rendered branded interface | Under 1.5 seconds on a simulated 4Mbps connection |
| SC-004 | Chart interactivity responsiveness | Time from metric toggle to interactive D3 chart render | Under 2 seconds for datasets up to 500 points |
| SC-005 | Mobile operational accessibility | Percentage of critical read-only operations feasible on 375px viewport | 100% (view subscriber status, view dispatcher health, view audit log) |
| SC-006 | Accessibility compliance | WCAG 2.1 AA contrast and keyboard navigation pass rate | 100% of primary user paths |
| SC-007 | Brand integrity | Instances of legacy OCP generic branding visible to authenticated users | Zero |
| SC-008 | Localization completeness | Percentage of branded text strings with translations in all 3 languages | 100% of strings introduced by the rebranding layer |
| SC-009 | Role-appropriate interface density | Percentage of read-only views where action columns are suppressed without PHP modification | 100% of designated read-only table views |
| SC-010 | Asset cache efficiency | Cache hit rate for TSiSIP branding assets across repeat page navigations | Above 95% after first page load per session |
| SC-011 | Build integrity | Time for asset build pipeline to complete from clean checkout | Under 30 seconds |
| SC-012 | Asset payload budget | Total uncompressed size of generated branding assets (excluding fonts) | At most 150KB |
| SC-013 | CSS specificity hygiene | Percentage of branded selectors requiring `!important` to override OCP defaults | Under 20% |
| SC-014 | Rollback safety | Verification that removing `web/tsisip/` and reverting two injection files restores original OCP | Original OCP renders identically |
| SC-015 | Layout stability | Cumulative Layout Shift during logo responsive swap on mobile | Under 0.05 |
| SC-016 | Security hygiene | Presence of inline event handlers in SVGs or `eval()` usage in TSiSIP JS | Zero instances |

---

## Key Entities

### Entity: Theme Configuration
- **Attributes**: primary palette hex values, accent palette hex values, typography family stack, logo asset paths (full/compact), breakpoint definitions, asset version hash
- **Relationships**: consumed by CSS compilation/rendering layer; referenced by chart color scales

### Entity: Asset Library
- **Attributes**: SVG icon collection (navigation, status, actions), logo variants (SVG full, SVG compact, PNG fallback), font files (WOFF2 primary, WOFF2 secondary), optimized CSS bundle
- **Relationships**: referenced by Theme Configuration; loaded by browser per-view

### Entity: Visualization Module
- **Attributes**: chart type registry (line, bar, gauge, heatmap), data endpoint mapping (OpenSIPS MI to JSON), color scale binding to Theme Configuration, container selector rules per OCP view
- **Relationships**: depends on Asset Library for styling; depends on OCP PHP view DOM structure for container injection

### Entity: OCP View Injection Point
- **Attributes**: file path (`web/css/main.css`, `web/common/header.php`), injection strategy (CSS import, PHP include, DOM-ready script), load order priority
- **Relationships**: targeted by Asset Library and Visualization Module; must remain compatible with OCP v9 core updates

---

## Scope

### In Scope
- Visual rebranding of all authenticated OCP v9 views under TSiSIP identity
- Design specification for TSiSIP logo, color palette, typography, and responsive behavior
- CSS/SVG asset optimization strategy and delivery mechanism
- D3.js interactive chart integration for SIP metrics and trunk analytics
- Responsive layout adaptations for tablet and mobile NOC access
- Full i18n localization into English, Spanish, and Portuguese via OCP gettext infrastructure
- Documentation of the Socratic decision framework and falsification hypotheses used to validate design choices

### Out of Scope
- Modification of OCP v9 PHP business logic, controllers, or SQL queries
- Replacement of the underlying OpenSIPS management interface (MI/FIFO) protocol
- Backend data pipeline or metric aggregation systems
- Multi-factor authentication or SSO integration
- Mobile native application development
- Real-time WebSocket push infrastructure for charts

---

## Dependencies

- OCP v9 source code baseline (PHP-native views, jQuery UI layer, existing CSS structure)
- OpenSIPS 3.6 LTS MI/FIFO or JSON-RPC metric endpoints for chart data sourcing
- TSiSIP corporate brand guidelines (logo usage rules, legal trademark constraints)
- Browser support matrix aligned with TSiSIP operator environments (minimum: evergreen desktop + tablet browsers)
- GNU gettext tooling (`msgfmt`, `xgettext`) for compiling `.po` files into `.mo` binaries consumed by OCP's PHP i18n layer

---

## Assumptions

- The OCP v9 CSS architecture permits thematic overrides through a single master stylesheet (`web/css/main.css`) without cascading specificity wars
- D3.js v7+ can be loaded alongside existing jQuery 3.x without script-loading conflicts via isolated initialization timing
- TSiSIP operator workstations have consistent access to local asset files; external CDN access is not guaranteed
- The rebranding layer will be maintained as a discrete, versioned artifact that can be rebased onto future OCP point releases
- Peak concurrent authenticated OCP sessions will not exceed 50 users during incident response peaks; asset delivery is optimized for this concurrency level

---

## Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| jQuery event handlers conflict with D3.js DOM manipulation | High | Medium | Isolate D3 initialization behind a DOM-ready guard; use shadow-DOM-like container isolation where feasible; test all interactive OCP modules with charts enabled |
| Inline styles in third-party OCP modules override TSiSIP theme | Medium | High | Audit top 5 most-used modules for inline styles; provide safe override rules only as last resort; document non-compliant modules for upstream patching |
| Font loading delays cause Flash of Unstyled Content on slow NOC links | Medium | Medium | Use font-display swap with system-ui fallback; preload critical font subset in header; measure and cap total font payload under 80KB |
| Logo aspect ratio causes header layout shift during responsive transition | Low | Medium | Define explicit width/height attributes on both logo variants; reserve header space with CSS aspect-ratio before image loads |
| D3.js chart renders empty on malformed MI data, confusing operators | High | Low | Implement empty-state and error-state visual components in TSiSIP branding; log chart failures silently to browser console for DevOps triage |
| Malicious or compromised SVG/D3.js assets execute in authenticated admin context | High | Low | Accept residual risk per Clarification Q1; mitigate via standard OCP session auth, file-system permissions on asset directories, and code-review of all injected SVG markup |

---

## Notes

### Socratic Decision Framework

The following self-questioning structure was applied to justify each major design choice:

1. **Why rebrand OCP rather than replace it with a modern SPA?**
   - *Question*: Would a ground-up frontend deliver more value than a cosmetic layer?
   - *Answer*: OCP v9 is a mature, stable PHP application with extensive module compatibility. Rewriting it would introduce regression risk, fragment the plugin ecosystem, and delay TSiSIP go-to-market. A thematic layer achieves brand coherence at a fraction of the cost and risk.

2. **Why D3.js instead of a lighter charting library?**
   - *Question*: Do we need the complexity of D3.js for OCP dashboards?
   - *Answer*: Telecom operators require bespoke visualizations (SIP trunk heatmaps, codec distribution donuts, jitter timeline streams) that off-the-shelf chart libraries render poorly. D3.js provides the necessary control over SVG generation, animation, and interactivity while respecting our strict color palette.

3. **Why SVG over icon fonts for the UI chrome?**
   - *Question*: Icon fonts are easier to implement; what justifies SVG?
   - *Answer*: Icon fonts fail to load in air-gapped environments, suffer from anti-aliasing artifacts at small sizes, and create accessibility challenges for screen readers. Inline SVGs are self-contained, crisply rendered at any density, and individually addressable for ARIA attributes.

4. **Why a cold-tone, metallic-blue palette?**
   - *Question*: Does this palette resonate with telecom operator expectations?
   - *Answer*: Telecom infrastructure branding conventionally signals reliability, precision, and low-temperature operational stability through blues and silvers. Warm palettes (oranges, reds) subconsciously suggest alarm or consumer-grade products. The metallic blue anchors trust in a high-availability SIP edge.

### Falsification Hypotheses (Karl Popper Method)

For each architectural decision, active hypotheses of failure were raised and mitigated:

| Decision | Falsification Hypothesis | How the Plan Falsifies/Mitigates |
|---|---|---|
| **CSS override strategy** | Hypothesis: A single main.css override will be too weak to override OCP's deeply nested selectors and inline styles, leading to a half-branded, inconsistent UI. | Falsification: Before committing to the override-only approach, a module-by-module CSS specificity audit will be performed on the 10 most-accessed OCP views. If more than 20% of branded elements require !important, the plan pivots to a build-time CSS-injection preprocessor that compiles per-module stylesheets. |
| **D3.js + jQuery coexistence** | Hypothesis: D3.js will attach event listeners that collide with jQuery's delegated event model, causing clicks on chart elements to bubble up and trigger unintended OCP modals or AJAX reloads. | Falsification: A prototype chart will be injected into the busiest OCP view (Subscriber list). Automated interaction tests (click, hover, toggle) will run for 100 cycles. If any jQuery event misfires, D3.js will be sandboxed inside an iframe or shadow DOM container. |
| **SVG asset performance** | Hypothesis: Replacing all PNG icons with inline SVGs will bloat HTML payload and increase TTFB on PHP pages that already render slowly. | Falsification: Benchmark the total HTML transfer size of the Dispatcher view before and after SVG injection. If the increase exceeds 30KB uncompressed, the plan switches to external SVG sprites referenced via use to leverage browser caching. |
| **Responsive table behavior** | Hypothesis: Making dense SIP data tables responsive will require hiding columns, which will render the interface unusable for operators who need to see all fields simultaneously. | Falsification: Conduct a card-sorting exercise with 3 representative user personas. If any critical column is ranked below must-see and cannot be accommodated in a 1024px viewport, the plan adopts horizontal scroll with sticky first-column context rather than column hiding. |
| **Logo responsive swap** | Hypothesis: Switching from full logo to compact icon on mobile will cause a layout shift (CLS > 0.1) that degrades perceived performance. | Falsification: Measure CLS using Lighthouse on the OCP dashboard view at 375px viewport. If CLS exceeds 0.05, reserve fixed-dimension placeholder boxes in the header so the swap is layout-stable. |
| **Font loading strategy** | Hypothesis: font-display swap will cause a visible font change (FOUT) that operators find jarring during emergency incident response. | Falsification: A/B test two loading strategies (swap vs. optional) with 5 NOC operators. If more than 2 operators report the FOUT as distracting under simulated incident pressure, preload the full font subset and accept a slightly slower initial paint. |
