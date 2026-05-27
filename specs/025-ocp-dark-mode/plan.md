# Implementation Plan: OCP Dark Mode

**Branch**: `025-ocp-dark-mode` | **Date**: 2026-05-26 | **Spec**: specs/025-ocp-dark-mode/spec.md

## Summary

Add a toggleable dark mode to the TSiSIP OCP using CSS custom properties, system preference detection, and persistent user settings.

## Technical Context

**Language**: PHP 8.2, CSS3, JavaScript  
**Primary Dependencies**: None (vanilla JS/CSS)  
**Storage**: localStorage + PHP session  
**Target Platform**: Modern browsers (Chrome, Firefox, Safari, Edge)  
**Project Type**: Web application frontend enhancement  

## Constitution Check

- No new Docker containers
- No new external dependencies
- Uses existing CSS variable system
- No database schema changes

## Project Structure

```
web/
├── common/
│   ├── header.php          # Add theme toggle + data-theme attribute
│   ├── set-theme.php       # AJAX endpoint to save theme preference
│   └── config.php          # Load theme from session
├── tsisip/
│   ├── css/
│   │   ├── tsisip-variables.css   # Add dark mode variables
│   │   └── tsisip-theme.css       # Ensure all colors use variables
│   └── js/
│       └── theme-toggle.js        # Theme switching logic
```

## Infrastructure & Deployment Plan

### Docker Changes
- None

### VPS Deploy Phase
- Standard OCP image build
- No special deployment steps

### Security Hardening
- Verify no inline hardcoded colors
- Ensure CSP compliance

## Complexity Tracking

No complexity violations.
