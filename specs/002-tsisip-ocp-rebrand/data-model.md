# Data Model: TSiSIP OCP Rebranding Assets

**Feature**: tsisip-ocp-rebrand
**Date**: 2026-05-17

---

## Entity: Theme Configuration

**Purpose**: Central source of truth for all visual design tokens.

| Attribute | Type | Description |
|---|---|---|
| `theme_id` | STRING | Primary identifier: `"tsisip-default"` |
| `primary_blue` | HEX | Metallic blue anchor: `#1A3A5C` |
| `primary_blue_light` | HEX | Lighter variant for hover states: `#2E5E8C` |
| `primary_blue_dark` | HEX | Darker variant for active states: `#0D1F33` |
| `surface_base` | HEX | Main background: `#F4F6F8` |
| `surface_card` | HEX | Card/panel background: `#FFFFFF` |
| `surface_header` | HEX | Header chrome: `#0A1628` |
| `text_primary` | HEX | Primary text on light surfaces: `#1A1A2E` |
| `text_secondary` | HEX | Secondary/muted text: `#5A6573` |
| `text_on_dark` | HEX | Text on dark surfaces: `#E8ECF0` |
| `accent_success` | HEX | Success states: `#0D8A6E` |
| `accent_warning` | HEX | Warning states: `#C4860C` |
| `accent_error` | HEX | Error states: `#B83232` |
| `accent_info` | HEX | Info states: `#2563A8` |
| `border_subtle` | HEX | Subtle dividers: `#D1D5DB` |
| `font_heading` | FONT_STACK | Primary typeface stack for headings |
| `font_body` | FONT_STACK | Secondary typeface stack for data/body |
| `font_mono` | FONT_STACK | Monospace stack for SIP numeric data |
| `breakpoint_tablet` | PX | Tablet threshold: `1024px` |
| `breakpoint_mobile` | PX | Mobile threshold: `768px` |
| `asset_version` | STRING | Build hash for cache busting |

**Constraints**:
- All colors must meet WCAG 2.1 AA contrast ratios against their designated surfaces.
- `asset_version` is regenerated on every theme build.

---

## Entity: Asset Library

**Purpose**: Collection of all physical assets consumed by the rebranding layer.

| Attribute | Type | Description |
|---|---|---|
| `asset_id` | STRING | Unique identifier (e.g., `logo-full`, `logo-compact`, `icon-nav-subscriber`) |
| `asset_type` | ENUM | `svg-inline`, `svg-sprite`, `woff2`, `css`, `png-fallback` |
| `logical_path` | STRING | Logical reference used by PHP views (e.g., `tsisip/logo-full`) |
| `hashed_path` | STRING | Physical filename with content hash (e.g., `tsisip-logo-full.a3f7c2.svg`) |
| `mime_type` | STRING | HTTP Content-Type header value |
| `size_bytes` | INT | Uncompressed file size |
| `load_priority` | ENUM | `critical` (above-the-fold), `deferred` (below-the-fold) |
| `role_visibility` | ARRAY | `["admin", "readonly", "all"]` — which roles require this asset |
| `i18n_dependent` | BOOLEAN | Whether the asset contains locale-specific text |

**Relationships**:
- Referenced by **Theme Configuration** via `asset_version`.
- Consumed by **OCP View Injection Point** at render time.

---

## Entity: Locale Bundle

**Purpose**: Translated strings for all TSiSIP-branded UI text.

| Attribute | Type | Description |
|---|---|---|
| `locale_id` | STRING | BCP 47 code: `en_US`, `es_ES`, `pt_BR` |
| `po_file_path` | STRING | Path to `.po` source file |
| `mo_file_path` | STRING | Path to compiled `.mo` binary |
| `translation_count` | INT | Number of TSiSIP-specific strings |
| `last_compiled` | TIMESTAMP | Last `msgfmt` compilation time |

**Constraints**:
- `translation_count` must match across all three locales (no missing translations).
- `.mo` files must be regenerated whenever `.po` files change.

---

## Entity: Chart Configuration

**Purpose**: Per-view D3.js chart definitions and data bindings.

| Attribute | Type | Description |
|---|---|---|
| `chart_id` | STRING | Unique identifier (e.g., `dispatcher-load`, `rtpengine-sessions`) |
| `view_module` | STRING | OCP module name where chart is injected (e.g., `dispatcher`, `rtpengine`) |
| `chart_type` | ENUM | `line`, `bar`, `gauge`, `heatmap`, `donut` |
| `data_endpoint` | STRING | OpenSIPS MI command or JSON-RPC method (e.g., `get_statistics load`) |
| `refresh_interval_sec` | INT | Auto-refresh cadence; `0` for manual only |
| `color_scale` | ARRAY | Ordered list of CSS variable names from Theme Configuration |
| `container_selector` | STRING | DOM selector for injection (e.g., `#tsisip-chart--dispatcher-load`) |
| `max_data_points` | INT | Maximum points rendered before downsampling |
| `empty_state_message_key` | STRING | i18n key for empty-state message |
| `error_state_message_key` | STRING | i18n key for error-state message |

**Constraints**:
- `container_selector` must use the `tsisip-chart--` prefix to avoid jQuery collisions.
- `max_data_points` defaults to `500` to satisfy SC-004.

---

## Entity: OCP View Injection Point

**Purpose**: Designated files where the TSiSIP layer integrates with OCP v9.

| Attribute | Type | Description |
|---|---|---|
| `injection_id` | STRING | Unique identifier (e.g., `header-php`, `main-css`) |
| `file_path` | STRING | Absolute path within OCP v9 (e.g., `web/common/header.php`) |
| `injection_type` | ENUM | `css-link`, `script-tag`, `php-include`, `meta-tag` |
| `load_order` | INT | Ordinal position relative to other injections |
| `conditional_logic` | STRING | PHP conditional if role-aware (e.g., `if ($_SESSION['user_role'] === 'admin')`) |
| `rollback_safe` | BOOLEAN | Whether removing the injection restores original OCP behavior |

**Constraints**:
- Only `web/css/main.css` and `web/common/header.php` may be modified (FR-007).
- All injections must be `rollback_safe = true`.

---

## Entity: Role Density Rule

**Purpose**: CSS selectors that adapt information density per user role.

| Attribute | Type | Description |
|---|---|---|
| `rule_id` | STRING | Unique identifier |
| `selector` | STRING | CSS selector (e.g., `[data-tsisip-role="readonly"] .actions-column`) |
| `property` | STRING | CSS property to apply (e.g., `display`) |
| `value` | STRING | CSS value (e.g., `none`) |
| `applies_to_roles` | ARRAY | Roles where rule is active: `["readonly"]` or `["admin"]` |
| `target_module` | STRING | OCP module scope (e.g., `subscriber`, `dispatcher`, `global`) |

**Constraints**:
- Rules must not use `!important` unless specificity audit justifies it.
- `target_module = "global"` applies across all views.
