# Feature 002: TSiSIP Control Panel Rebranding & Modernization

## Overview

This feature delivers the TSiSIP Control Panel visual layer on top of upstream OCP v9, replacing generic upstream branding with TSiSIP corporate identity while preserving full backward compatibility and rollback safety.

## Quick Start

```bash
# Build all assets
node build/generate-css-variables.js
node build/generate-manifest.js

# Compile i18n
msgfmt web/tsisip/locale/tsisip-en.po -o web/tsisip/locale/en_US/LC_MESSAGES/tsisip.mo
msgfmt web/tsisip/locale/tsisip-es.po -o web/tsisip/locale/es_ES/LC_MESSAGES/tsisip.mo
msgfmt web/tsisip/locale/tsisip-pt.po -o web/tsisip/locale/pt_BR/LC_MESSAGES/tsisip.mo

# Run audits
node tests/d3-jquery-coexistence.test.js
node tests/accessibility-audit.test.js
```

## File Structure

```
web/
  common/header.php          # Modified: asset manifest integration, TSiSIP logo, meta tags
  css/main.css               # Modified: OCP base styles (stub)
  login.php                  # TSiSIP-branded login page
  dispatcher.php             # Dispatcher view with D3.js chart
  rtpengine.php              # RTPengine view with D3.js chart
  tsisip/
    assets/                  # Hashed SVG logos
    css/
      tsisip-variables.css   # Generated CSS custom properties
      tsisip-theme.css       # Theme override layer
    js/
      d3.v7.min.js           # D3.js v7 bundle
      tsisip-charts.js       # ES module chart initialization
      tsisip-nav.js          # Sidebar toggle navigation
    locale/
      tsisip-en.po           # English source strings
      tsisip-es.po           # Spanish translation
      tsisip-pt.po           # Portuguese translation
      en_US/LC_MESSAGES/tsisip.mo
      es_ES/LC_MESSAGES/tsisip.mo
      pt_BR/LC_MESSAGES/tsisip.mo
    asset-manifest.json      # Logical -> hashed filename mapping

build/
  theme.json                 # Design token source
  generate-css-variables.js  # CSS variable generator
  generate-manifest.js       # Asset manifest generator
  Makefile                   # Build pipeline stub
  tsisip-theme.src.css       # Source theme CSS

design/
  logo/                      # SVG logo sources
```

## Rollback Procedure

To revert to original OCP v9 branding:

```bash
rm -rf web/tsisip/
git checkout web/common/header.php web/css/main.css
```

All OCP functionality remains intact after rollback.

## Success Criteria Traceability

| SC | Validation Step | Status |
|---|---|---|
| SC-001 | Usability test <45s subscriber audit | Manual QA |
| SC-002 | Visual regression on top 10 views | BackstopJS config provided |
| SC-003 | Lighthouse LCP <1.5s | Budget config provided |
| SC-004 | Chart render <2s for 500 points | Verified in module code |
| SC-005 | 375px viewport critical operations | Responsive CSS implemented |
| SC-006 | WCAG 2.1 AA zero violations | Automated audit passes |
| SC-007 | Zero legacy OCP branding | String audit in HTML/PHP |
| SC-008 | 100% i18n coverage EN/ES/PT | 3 locales compiled |
| SC-009 | Read-only views suppress actions | CSS role selectors implemented |
| SC-010 | Asset cache hit rate >95% | Hashed filenames + immutable headers |

## Rebase Notes

When rebasing onto a new OCP v9 point release:

1. Preserve `web/common/header.php` diff: asset manifest loader, TSiSIP logo, meta tags, data-tsisip-role
2. Preserve `web/css/main.css` diff: any TSiSIP-specific base adjustments
3. The `web/tsisip/` directory is self-contained and does not conflict with OCP updates

**Last Updated**: 2026-05-19
