# Feature Specification: OCP Dark Mode

## Overview

**Feature**: OCP Dark Mode  
**Short name**: dark-mode  
**Created**: 2026-05-26  
**Status**: Complete  

### Context

The TSiSIP OCP currently uses a light theme based on the TSiSIP premium branding. Operators working night shifts or in low-light environments need a dark mode option to reduce eye strain.

### Objective

Provide a toggleable dark mode for the entire OCP interface that respects user preference and persists across sessions.

---

## User Scenarios & Testing

### Scenario 1: Enable Dark Mode
- **Given** the user is logged into the OCP
- **When** the user clicks the dark mode toggle in the header
- **Then** the interface switches to dark theme immediately
- **And** the preference is saved for the next session

### Scenario 2: System Preference Detection
- **Given** the user has not set a preference
- **When** the user loads the OCP
- **Then** the theme matches the system/browser preference (`prefers-color-scheme`)

### Scenario 3: Dark Mode in All Pages
- **Given** dark mode is enabled
- **When** the user navigates to any OCP page
- **Then** the dark theme is applied consistently

---

## Functional Requirements

### FR-025-001: Dark Mode Toggle
**Description**: A toggle switch in the header allows users to switch between light and dark modes.  
**Acceptance Criteria**:
- Toggle is visible on all pages
- Toggle state reflects current mode
- Clicking toggle switches mode immediately without page reload
- Preference is saved to `localStorage` and server-side session

### FR-025-002: CSS Variable System
**Description**: All colors use CSS custom properties that can be overridden for dark mode.  
**Acceptance Criteria**:
- Light mode variables in `:root`
- Dark mode variables in `[data-theme="dark"]`
- No hardcoded colors in CSS
- All existing pages use variables

### FR-025-003: System Preference Detection
**Description**: If no user preference is set, detect system preference.  
**Acceptance Criteria**:
- Check `window.matchMedia('(prefers-color-scheme: dark)')` on load
- Apply dark mode if system prefers dark
- Do not override explicit user preference

### FR-025-004: Theme Persistence
**Description**: Theme preference persists across sessions.  
**Acceptance Criteria**:
- Save preference to `localStorage`
- Save preference to server session via AJAX
- Restore on page load from `localStorage` first, then session

### FR-025-005: Accessibility
**Description**: Dark mode meets WCAG 2.1 AA contrast requirements.  
**Acceptance Criteria**:
- All text has contrast ratio >= 4.5:1
- Interactive elements have contrast ratio >= 3:1
- Focus indicators remain visible

---

## Security Requirements

| ID | Requirement | Verification |
|---|---|---|
| SR-001 | Theme preference stored securely | No sensitive data in localStorage |
| SR-002 | No CSP violations | Inline styles use CSS variables only |

## Docker & Infrastructure Requirements

No changes required.

## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-001 | All pages support dark mode | Visual inspection | 100% pages |
| SC-002 | Contrast ratio compliance | axe-core audit | 0 violations |
| SC-003 | Preference persistence | User test | Works across sessions |

## Scope

### In Scope
- Dark mode CSS variables
- Header toggle switch
- System preference detection
- Preference persistence
- Accessibility compliance

### Out of Scope
- Custom color picker
- Per-user theme admin setting
- Third-party widget theming

## Dependencies

- Feature 002 (OCP Rebrand) — must be complete
- CSS variable system in `tsisip-variables.css`

## Assumptions

- All existing CSS uses the variable system
- `localStorage` is available in target browsers

## Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| Inline styles bypass variables | Medium | Medium | Audit all inline styles |
| Third-party CSS incompatible | Low | Low | Override with `!important` as last resort |
