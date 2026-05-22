# Research: TSiSIP OCP Rebranding & Modernization

**Feature**: tsisip-ocp-rebrand
**Date**: 2026-05-17
**Status**: Complete

---

## R-001: OCP v9 Frontend Architecture

**Decision**: OCP v9 is a PHP-native application using server-rendered views, jQuery 3.x for DOM manipulation, and a monolithic CSS file (`web/css/main.css`). No modern JS framework is present.

**Rationale**: The rebranding must work within this constraint. We cannot introduce React, Vue, or Angular without rewriting views. The theme must be a CSS/SVG/JS injection layer.

**Alternatives considered**:
- Rewrite OCP frontend in a modern SPA framework → Rejected: violates FR-002-007 (non-breaking), too risky, fragments plugin ecosystem.
- Use a CSS-only approach with zero JavaScript → Rejected: D3.js charts require JS; responsive sidebar toggle requires JS.

---

## R-002: CSS Override Strategy for Legacy PHP Apps

**Decision**: Use a single compiled CSS file (`tsisip-theme.css`) loaded after OCP's `main.css`, leveraging CSS custom properties (variables) for palette/theming and targeted specificity for overrides.

**Rationale**: OCP's CSS uses generic class names and some inline styles. CSS variables allow runtime theme switching without JS. Specificity-based overrides avoid `!important` unless audit threshold exceeds 20% (per falsification hypothesis in spec).

**Alternatives considered**:
- Per-module stylesheet compilation → Deferred: only triggered if specificity audit fails.
- CSS-in-JS or Shadow DOM → Rejected: incompatible with jQuery-based OCP plugins.

---

## R-003: D3.js + jQuery Coexistence

**Decision**: Load D3.js v7 as an ES module or IIFE in isolated `<script type="module">` blocks, with chart initialization deferred to `DOMContentLoaded`. Chart containers use dedicated `<div>` IDs with `tsisip-chart--` prefix to avoid selector collision.

**Rationale**: D3.js v7 is namespace-clean when loaded as a module. Isolating chart containers prevents event bubbling conflicts. The falsification test (100 interaction cycles) will validate this before full rollout.

**Alternatives considered**:
- Load D3.js via global `<script>` tag → Rejected: pollutes window namespace, higher collision risk.
- iframe sandboxing for charts → Deferred: only if interaction tests fail.

---

## R-004: SVG vs Icon Fonts

**Decision**: Use inline SVG for critical chrome (logo, nav icons, status badges) and external SVG sprite sheets for decorative icons.

**Rationale**: Inline SVG eliminates HTTP requests for above-the-fold assets. Sprite sheets reduce payload for below-the-fold icons. Both are air-gapped-friendly, unlike icon fonts which fail silently when font files are blocked.

**Alternatives considered**:
- Font Awesome or similar icon font → Rejected: external dependency, font-loading issues, poor screen-reader support.
- PNG sprite sheets → Rejected: raster scaling artifacts, larger file sizes, no CSS color manipulation.

---

## R-005: Asset Versioning & Cache Busting

**Decision**: Use filename hashing (e.g., `tsisip-main.a3f7c2.css`) generated at build time, with a manifest JSON mapping logical names to hashed filenames. PHP includes read this manifest to emit correct `<link>` and `<script>` tags.

**Rationale**: Immutable cache headers + hashed filenames is the most robust cache-busting strategy. Query-string cache busting is less reliable with some reverse proxies. A PHP-readable manifest keeps the injection point (`header.php`) dynamic without hard-coding hashes.

**Alternatives considered**:
- Query-string versioning (`?v=1.2.3`) → Rejected: less reliable with aggressive proxies, manual version management.
- Service Worker with background sync → Rejected: over-engineered for 50 concurrent users; adds complexity.

---

## R-006: OCP i18n Infrastructure

**Decision**: OCP v9 uses GNU gettext with `.po`/`.mo` files in `config/locale/`. New TSiSIP strings will be added to dedicated `tsisip-en.po`, `tsisip-es.po`, and `tsisip-pt.po` files, compiled alongside existing OCP locale files.

**Rationale**: This integrates with OCP's existing `bindtextdomain` setup without modifying core locale files. It also allows independent updates to TSiSIP branding strings without conflicting with OCP upstream translations.

**Alternatives considered**:
- Hard-code English strings → Rejected: violates Clarification Q2 (full i18n day one).
- Override OCP core `.po` files → Rejected: fragile on OCP updates, merge conflicts.

---

## R-007: Color Palette Implementation

**Decision**: Define the TSiSIP palette as CSS custom properties in `:root`, with semantic aliases (e.g., `--tsisip-primary`, `--tsisip-surface`, `--tsisip-accent-success`). Charts read these values via `getComputedStyle` for dynamic theming.

**Rationale**: CSS variables are supported by all evergreen browsers and allow runtime theme switching. D3.js can read them via `getComputedStyle(document.documentElement).getPropertyValue('--tsisip-primary')`, ensuring charts always match the UI theme.

**Alternatives considered**:
- SASS/LESS variables compiled at build time → Rejected: requires build tooling not present in OCP; static output cannot be themed at runtime.
- JSON theme file loaded by JS → Rejected: adds HTTP request and parse overhead; CSS variables are native.

---

## R-008: Responsive Table Strategy

**Decision**: Use CSS `overflow-x: auto` with `position: sticky` on the first column for horizontal scroll on mobile. Action columns are suppressed for read-only users via CSS attribute selectors (`[data-role="readonly"] td.actions { display: none; }`).

**Rationale**: Horizontal scroll preserves all columns without hiding data. Sticky first column maintains row context. CSS-only suppression satisfies FR-002-007 (no PHP modification). The falsification test (card-sorting) confirmed this approach.

**Alternatives considered**:
- Column hiding via JS breakpoints → Rejected: requires PHP modification to mark columns as hideable.
- Card-based layout for mobile → Rejected: too radical a departure from OCP's table-centric UX; high retraining cost.
