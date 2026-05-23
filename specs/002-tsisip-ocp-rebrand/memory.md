# Feature 002 Memory: TSiSIP Control Panel Rebranding & Modernization

## Current Scope
Transform OCP v9 into a TSiSIP-branded operational portal with a cold-tone metallic-blue visual identity, D3.js interactive charts, full i18n (EN/ES/PT), responsive layouts, and role-aware information density — without modifying PHP business logic.

## Relevant Decisions
- **Thematic layer over rewrite**: Rebranding OCP via CSS/SVG/JS injection is lower risk and faster than rewriting the PHP application.
- **D3.js for charts**: Off-the-shelf chart libraries poorly render telecom-specific visualizations (SIP trunk heatmaps, jitter timelines). D3.js provides necessary control.
- **SVG over icon fonts**: Inline SVGs work in air-gapped environments, render crisply at any density, and support ARIA attributes.
- **Cold-tone metallic-blue palette**: Signals reliability and precision aligned with telecom infrastructure conventions.
- **CSS override strategy**: Single master override layer (tsisip-theme.css) applied after OCP main.css; rollback-safe by removing web/tsisip/ and reverting two injection files.

## Active Architecture Constraints
- No PHP view files modified except web/common/header.php and web/css/main.css.
- Total uncompressed asset payload <=150KB (excluding font files).
- CSS specificity: <20% of branded selectors may require !important.
- D3.js v7 loaded as <script type="module"> to isolate from jQuery.
- All strings externalized into .po files; no hard-coded English in CSS/JS.

## Accepted Deviations
- No additional CSP, SRI, or server-side SVG sanitization beyond standard OCP v9 auth (Clarification Q1, Option A).

## Relevant Security Constraints
- Trust all local assets implicitly (no CDN dependencies).
- No inline event handlers in SVGs; no eval() in TSiSIP JS.
- Peak concurrency target: 50 authenticated sessions.

## Related Historical Lessons
- CSS specificity audit on top 10 OCP views prevents !important explosion; pivot to per-module stylesheets if >20% require it.
- D3.js isolation via ES module scope prevents jQuery event delegation collisions.
- Font-display swap with system-ui fallback prevents FOIT on slow NOC links.
- Responsive tables use horizontal scroll with sticky first column rather than column hiding to preserve data density.

## Conflict Warnings
- Feature 010 (Navigation System Links) adds system management links to the dashboard that depend on the TSiSIP theme CSS classes (tsisip-btn-primary, etc.).
- Feature 016 (Audit Log) adds dashboard links that must align with the existing TSiSIP branding and responsive layout.

## Retrieval Notes
- Search terms: OCP rebrand, TSiSIP theme, D3.js charts, asset manifest, i18n, CSS override, logo, palette, typography.
- Related features: 010 (navigation), 011 (forced password change), 016 (audit log).
