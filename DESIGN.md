---
title: "TSiSIP Design System"
description: "Visual identity and design system for TSiSIP OpenSIPS Control Panel v9"
version: "1.0.0"
---

# TSiSIP Design System

## Overview

The TSiSIP design system defines the visual language for the OpenSIPS Control Panel (OCP) v9 rebranding. It replaces generic OCP chrome with a premium, cold-tone metallic identity that conveys trust, stability, and technical precision — qualities essential for telecom operators managing SIP infrastructure.

## Philosophy

- **Trust through restraint**: Deep metallic blues and generous whitespace signal reliability.
- **Density without clutter**: Information-rich tables remain scannable via hierarchy, not decoration.
- **Role-aware adaptivity**: The same visual identity serves administrators, NOC operators, and read-only users through density modulation, not separate themes.
- **Air-gapped compatibility**: Zero external CDN dependencies; all assets are self-hosted.

---

## Color Palette

### Brand Colors

| Token | Hex | Usage |
|---|---|---|
| `--tsisip-primary-blue` | `#1A3A5C` | Primary anchor; headers, active states, logo |
| `--tsisip-primary-blue-light` | `#2E5E8C` | Hover states, focus rings |
| `--tsisip-primary-blue-dark` | `#0D1F33` | Active/pressed states |

### Surface Colors

| Token | Hex | Usage |
|---|---|---|
| `--tsisip-surface-base` | `#F4F6F8` | Page background |
| `--tsisip-surface-card` | `#FFFFFF` | Cards, panels, modals |
| `--tsisip-surface-header` | `#0A1628` | Top navigation chrome |

### Text Colors

| Token | Hex | Usage |
|---|---|---|
| `--tsisip-text-primary` | `#1A1A2E` | Body text on light surfaces |
| `--tsisip-text-secondary` | `#5A6573` | Muted labels, placeholders |
| `--tsisip-text-on-dark` | `#E8ECF0` | Text on dark surfaces (header, dark buttons) |

### Semantic Accents

| Token | Hex | Usage |
|---|---|---|
| `--tsisip-accent-success` | `#0D8A6E` | Healthy targets, success messages |
| `--tsisip-accent-warning` | `#C4860C` | Degraded targets, cautions |
| `--tsisip-accent-error` | `#B83232` | Failed targets, errors |
| `--tsisip-accent-info` | `#2563A8` | Neutral status, info badges |
| `--tsisip-border-subtle` | `#D1D5DB` | Dividers, input borders |

### Contrast Requirements

All color combinations must meet **WCAG 2.1 AA**:
- Normal text: contrast ratio >= 4.5:1
- Large text (>=18pt or >=14pt bold): contrast ratio >= 3:1

Verified pairs:
- `#1A3A5C` on `#F4F6F8` -> 11.2:1 PASS
- `#E8ECF0` on `#0A1628` -> 14.8:1 PASS
- `#1A1A2E` on `#FFFFFF` -> 15.1:1 PASS

---

## Typography

### Font Stacks

| Role | Stack | Fallback |
|---|---|---|
| Headings | Inter | Segoe UI, system-ui, -apple-system, sans-serif |
| Body / Data | Inter | Segoe UI, system-ui, -apple-system, sans-serif |
| Monospace | JetBrains Mono | Fira Code, Consolas, monospace |

### Type Scale

| Token | Size | Usage |
|---|---|---|
| `--tsisip-text-xs` | 0.75rem (12px) | Badges, timestamps, meta |
| `--tsisip-text-sm` | 0.875rem (14px) | Table cells, buttons, labels |
| `--tsisip-text-base` | 1rem (16px) | Body text, inputs |
| `--tsisip-text-lg` | 1.125rem (18px) | Section headings |
| `--tsisip-text-xl` | 1.25rem (20px) | Card titles |
| `--tsisip-text-2xl` | 1.5rem (24px) | Page headings |
| `--tsisip-text-3xl` | 1.875rem (30px) | Hero / login titles |
| `--tsisip-text-4xl` | 2.25rem (36px) | Marketing headers only |

### Rules

- Use `font-weight: 500` for UI labels and buttons; `700` for page headings only.
- Monospace is reserved for SIP URIs, IP addresses, HA1 hashes, and numeric data.
- Line-height: `1.5` for body, `1.25` for headings.

---

## Spacing & Layout

### Border Radius

| Element | Radius |
|---|---|
| Buttons, inputs, badges | `6px` |
| Cards, panels | `8px`–`12px` |
| Logo container | `6px`–`8px` |

### Shadows

| Context | Value |
|---|---|
| Login card | `0 10px 40px rgba(0, 0, 0, 0.2)` |
| Modal / dropdown | `0 4px 12px rgba(0, 0, 0, 0.1)` |
| None on standard cards | Flat design; rely on borders and background contrast |

---

## Components

### Header

- Background: `--tsisip-surface-header`
- Height: `56px`
- Padding: `0.75rem 1.5rem`
- Logo: TSiSIP horizontal lockup (>=768px); compact icon (<768px)
- Mobile: hamburger toggle (`44px x 44px`), left-aligned

### Sidebar Navigation

- Width: `240px` (desktop)
- Background: `--tsisip-surface-card`
- Border-right: `1px solid --tsisip-border-subtle`
- Collapse behavior:
  - >=1024px: always visible
  - <1024px: off-canvas overlay, transform-based animation
- Transition: `transform 0.25s ease`

### Buttons

| Variant | Background | Text | Border |
|---|---|---|---|
| Primary | `--tsisip-primary-blue` | `--tsisip-text-on-dark` | `--tsisip-primary-blue` |
| Secondary | transparent | `--tsisip-primary-blue` | `--tsisip-border-subtle` |

- Min height: `44px`
- Min width: `44px`
- Padding: `0.5rem 1rem`
- Border-radius: `6px`

### Data Tables

- Background: `--tsisip-surface-card`
- Header row: `--tsisip-surface-header` with `--tsisip-text-on-dark`
- Zebra striping: even rows `--tsisip-surface-base`
- Hover: `rgba(26, 58, 92, 0.04)`
- Monospace cells for SIP data
- Mobile: `overflow-x: auto` + sticky first column

### Badges

| Type | Background | Text |
|---|---|---|
| Success | `rgba(13, 138, 110, 0.12)` | `--tsisip-accent-success` |
| Warning | `rgba(196, 134, 12, 0.12)` | `--tsisip-accent-warning` |
| Error | `rgba(184, 50, 50, 0.12)` | `--tsisip-accent-error` |
| Info | `rgba(37, 99, 168, 0.12)` | `--tsisip-accent-info` |

- Padding: `0.125rem 0.5rem`
- Border-radius: `9999px` (pill)
- Font-size: `--tsisip-text-xs`

### Form Inputs

- Background: `--tsisip-surface-card`
- Border: `1px solid --tsisip-border-subtle`
- Border-radius: `6px`
- Min-height: `44px`
- Focus: `border-color: --tsisip-primary-blue-light`, `box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.15)`

---

## Responsive Breakpoints

| Name | Width | Behavior |
|---|---|---|
| Desktop | >=1024px | Full sidebar, multi-column forms, full logo |
| Tablet | 768px–1023px | Collapsed sidebar, stacked forms, full logo |
| Mobile | <768px | Off-canvas sidebar, single-column forms, compact logo |

### Touch Targets

All interactive elements must be >=44px x 44px.

---

## Role Density

Density is controlled entirely via CSS attribute selectors on `<body data-tsisip-role="...">`.

| Role | Effect |
|---|---|
| `admin` | Full UI: all columns, all buttons, all navigation items |
| `user` | Standard UI: same as admin (placeholder for future granularity) |
| `readonly` | Suppressed UI: hidden action columns, hidden edit/delete buttons, hidden admin-only nav items |

Implementation: `[data-tsisip-role="readonly"] .actions-column { display: none; }`

No PHP logic changes required.

---

## Asset Strategy

### Icons

- **Inline SVG** for critical chrome (logo, nav icons, status badges)
- **SVG sprite sheets** for decorative icons via `<use>`
- No icon fonts (air-gap compatibility, no FOIT/FOUT)

### Images

- Logo variants: full horizontal, compact icon, monochrome fallbacks
- All SVGs optimized with `svgo`
- Alt text required on all images

### Cache Busting

- Filenames include content hash: `tsisip-theme.a3f7c2.css`
- `Cache-Control: public, max-age=31536000, immutable`
- PHP reads `asset-manifest.json` to emit correct hashed URLs

---

## Accessibility

- WCAG 2.1 AA compliance mandatory
- Keyboard navigation for sidebar toggle, buttons, form fields
- `aria-label` on icon-only buttons
- `aria-expanded` on collapsible navigation
- Screen-reader-only text via `.tsisip-sr-only` utility
- Focus-visible outlines on all interactive elements

---

## Animation & Motion

- Sidebar transition: `transform 0.25s ease`
- Button hover: `background-color 0.15s ease`
- Input focus: `border-color 0.15s ease, box-shadow 0.15s ease`
- Respect `prefers-reduced-motion`: disable transforms if user prefers reduced motion

---

## Anti-Patterns

| Do Not | Do Instead |
|---|---|
| Use `!important` on >20% of selectors | Audit specificity per-module; pivot to per-module stylesheets if threshold exceeded |
| Load D3.js globally on every page | Lazy-load only on views containing charts |
| Hard-code English strings in CSS/JS | Use `data-i18n` attributes or runtime JS gettext helper |
| Use external CDN for fonts or icons | Self-host all assets; inline critical SVGs |
| Modify PHP business logic for theming | Achieve all visual changes via CSS and header.php injection only |

---

Last updated: 2026-05-17
