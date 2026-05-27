# TSiSIP OCP — New Pages Architecture Document

**Date**: 2026-05-26  
**Feature**: 002-tsisip-ocp-rebrand (backlog completion)  
**Author**: Autonomous Speckit Orchestration  

---

## Overview

This document describes the architecture of 9 new pages added to the TSiSIP OCP during the Feature 002 backlog completion sprint.

---

## Page Inventory

| Page | File | Role Access | MI Integration | Data Source |
|---|---|---|---|---|
| Gateway Health | `web/gateway-health.php` | devops+ | `uac_reg_list`, `ds_list` | MI HTTP |
| Live Call Queue | `web/call-queue.php` | devops+ | `t_list`, `get_statistics` | MI HTTP + PostgreSQL |
| Network Topology | `web/topology.php` | devops+ | `get_statistics` | MI HTTP (best effort) |
| Manual Failover | `web/failover.php` | admin | `ds_reload`, `ds_set_state` | MI HTTP (mutating) |
| Alert History | `web/alert-history.php` | devops+ | N/A | PostgreSQL `auth_audit_log` |
| RTPengine Status | `web/rtpengine-status.php` | devops+ | `rtpengine_show`, `rtpengine_list` | MI HTTP |
| Subscriber Statistics | `web/subscriber-stats.php` | devops+ | N/A | PostgreSQL `subscriber` |
| System Configuration | `web/system-config.php` | admin | `get_statistics` | MI HTTP |
| Export Text | `web/common/export-text.php` | N/A (utility) | N/A | N/A |

---

## Security Model

### Authentication
- All pages require `requireAuth()`
- Session validated via PostgreSQL `ocp_users` table
- Password change enforcement via `checkPasswordChange()`

### Authorization
- `devops` role: gateway-health, call-queue, topology, alert-history, rtpengine-status, subscriber-stats
- `admin` role: failover, system-config (in addition to devops pages)
- Role hierarchy: readonly < user < assistant < dentist < devops < admin

### CSRF Protection
- All forms include `csrfInput()`
- `validateCsrfToken()` checked on POST requests
- Tokens are 32-byte random hex values stored in session

### Audit Logging
- Every page view logged via `logAuditEvent()`
- Failover operations logged with full context
- Export operations logged with filter parameters
- Language changes logged

---

## MI Integration Pattern

### Circuit Breaker
- `miHttpCall()` implements circuit breaker pattern
- 3 failures in 30s window opens circuit for 60s
- Cache TTL: 5s to prevent MI overload

### Error Handling
- MI unreachable: warning badge displayed
- Invalid JSON: circuit breaker increments
- Empty data: info badge displayed

### Commands Used
| Command | Module | Mutating |
|---|---|---|
| `uac_reg_list` | uac_registrant | No |
| `ds_list` | dispatcher | No |
| `ds_reload` | dispatcher | Yes |
| `ds_set_state` | dispatcher | Yes |
| `t_list` | tm | No |
| `dlg_list` | dialog | No |
| `get_statistics` | core | No |
| `rtpengine_show` | rtpengine | No |
| `rtpengine_list` | rtpengine | No |

---

## i18n Architecture

### Locale Initialization
- `web/common/config.php`: `setlocale()`, `bindtextdomain()`, `textdomain()`
- Session-based: `$_SESSION['lang']`
- Fallback: `OCP_DEFAULT_LANG` env var or `en_US`

### Supported Languages
| Code | Label | File |
|---|---|---|
| en_US | English | `web/tsisip/locale/en_US/LC_MESSAGES/tsisip.mo` |
| es_ES | Español | `web/tsisip/locale/es_ES/LC_MESSAGES/tsisip.mo` |
| pt_BR | Português | `web/tsisip/locale/pt_BR/LC_MESSAGES/tsisip.mo` |

### Translation Workflow
1. `xgettext` extracts strings from PHP files
2. `msgmerge` updates existing `.po` files
3. Dictionary-based translation for ES/PT
4. `msgfmt` compiles `.mo` files

---

## Frontend Patterns

### Metric Cards
```html
<div class="tsisip-metric-card">
    <div class="tsisip-metric-label">Label</div>
    <div class="tsisip-metric-value">Value</div>
</div>
```

### Status Badges
| Status | Class |
|---|---|
| Success | `tsisip-badge tsisip-badge-success` |
| Warning | `tsisip-badge tsisip-badge-warning` |
| Error | `tsisip-badge tsisip-badge-error` |
| Info | `tsisip-badge tsisip-badge-info` |
| Neutral | `tsisip-badge tsisip-badge-neutral` |

### Auto-Refresh
- 15-second polling interval
- Lightweight JS fetch loop
- Graceful degradation on failure

---

## Testing Strategy

### Integration Tests
- `tests/integration/test-ocp-new-pages.sh`
- Verifies HTTP 200 for each page
- Verifies CSRF token presence
- Verifies i18n string presence

### Manual Validation
1. Build OCP image: `docker build -t tsisip/ocp:latest -f docker/ocp/Dockerfile .`
2. Start stack: `docker compose up -d`
3. Login as Admin
4. Navigate to each new page
5. Verify MI data loads (if OpenSIPS is running)
6. Verify language switching works

---

## Performance Considerations

- MI calls cached for 5s
- Circuit breaker prevents MI overload
- Pagination on alert-history (25 per page)
- AJAX refresh only on real-time pages
- No host-published ports on Asterisk/PostgreSQL

---

## Future Enhancements

- [ ] WebSocket support for real-time updates (replaces polling)
- [ ] Dark mode toggle
- [ ] Export to PDF/Excel
- [ ] Custom dashboard layouts
- [ ] Role-based menu customization
- [ ] Mobile-responsive sidebar
- [ ] Keyboard shortcuts
- [ ] Accessibility audit (WCAG 2.1 AA)
