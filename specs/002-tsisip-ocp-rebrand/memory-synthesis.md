# Feature 002 Memory Synthesis: TSiSIP Control Panel Rebranding & Modernization

## Current Scope
TSiSIP-branded OCP v9 with metallic-blue identity, D3.js charts, i18n (EN/ES/PT), responsive layout, and role-aware density — zero PHP logic changes.

## Relevant Decisions
- Thematic layer over OCP rewrite (lower risk).
- D3.js for bespoke telecom visualizations.
- SVG over icon fonts (air-gapped, crisp, accessible).
- CSS override layer with rollback safety.

## Active Architecture Constraints
- Only header.php and main.css may be modified.
- Asset payload <=150KB uncompressed.
- <20% selectors using !important.
- D3.js isolated as ES module.
- All strings in .po files.

## Accepted Deviations
- No additional CSP/SRI beyond OCP v9 auth.

## Relevant Security Constraints
- Local assets only; no CDN.
- No inline SVG event handlers or eval().

## Related Historical Lessons
- CSS specificity audit prevents override wars.
- ES module scope prevents jQuery/D3.js collisions.
- Horizontal scroll + sticky column preserves table density on mobile.

## Conflict Warnings
- Feature 010 navigation and Feature 016 audit links depend on TSiSIP theme CSS classes.

## Retrieval Notes
- Keywords: OCP rebrand, TSiSIP theme, D3.js charts, asset manifest, i18n, CSS override.
- Related: 010, 011, 016.
