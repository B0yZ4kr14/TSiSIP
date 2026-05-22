# Security Assessment — Feature 020: OCP Critical Tool Gap Closure

**Document ID**: SEC-020-EVI-001  
**Date**: 2026-05-19  
**Status**: Complete  
**Risk Level**: MEDIUM  
**MSL Applicability**: MSL-Relevant (justified in §6)  

---

## 1. Executive Summary

Feature 020 implements six new administrative tools for the TSiSIP OCP frontend: Dialog Viewer, MI Commands Runner, Statistics Monitor, Dialplan Manager, Domains Manager, and TLS Management UI. This assessment evaluates the security posture of these tools, their data handling, and integration with the TSiSIP project.

**Overall Finding**: The new tools expose OpenSIPS runtime state and control interfaces. Risk is MEDIUM due to the potential for information disclosure and unauthorized command execution. All tools are protected by RBAC, CSRF tokens, and audit logging.

---

## 2. Threat Model

| ID | Threat | Likelihood | Impact | Risk | Mitigation |
|---|---|---|---|---|---|
| T-01 | MI command injection via web UI | Low | Critical | High | §3.2 (whitelist), §3.4 (input validation) |
| T-02 | Dialog data exposure (SIP URIs, IPs) | Medium | Medium | Medium | §3.3 (RBAC), §4.1 (read-only) |
| T-03 | Unauthorized TLS certificate reload | Low | High | Medium | §3.3 (admin-only), §4.3 (audit log) |
| T-04 | XSS via MI command output | Low | Medium | Low | §3.4 (htmlspecialchars), §4.2 (output encoding) |
| T-05 | SQL injection in dialplan/domains CRUD | Low | High | Medium | §3.4 (PDO prepared statements) |
| T-06 | Privilege escalation to admin functions | Low | High | Medium | §3.3 (role hierarchy), §4.4 (admin gate) |

---

## 3. Security Controls

### 3.1 Authentication & Authorization
- All tools require `requireRole('devops')` minimum.
- TLS reload and `dlg_end_dlg` require `requireRole('admin')`.
- Role hierarchy: `viewer` < `devops` < `admin`.

### 3.2 MI Command Whitelist
- Only pre-approved commands may be executed:
  - `ds_reload` (devops+)
  - `tls_reload` (admin only)
  - `get_statistics` (devops+)
  - `dlg_list` (devops+)
  - `dlg_end_dlg` (admin only)
  - `domain_reload` (devops+)
- Non-whitelisted commands are rejected with HTTP 403.
- The whitelist is hardcoded in PHP, not user-configurable.

### 3.3 Role-Based Access Control
| Tool | Read | Write | Admin Action |
|---|---|---|---|
| Dialog Viewer | devops+ | — | — |
| MI Commands | devops+ | — | `dlg_end_dlg`, `tls_reload` |
| Statistics | devops+ | — | — |
| Dialplan | devops+ | devops+ | — |
| Domains | devops+ | devops+ | — |
| TLS Management | devops+ | — | `tls_reload` |

### 3.4 Input Validation & Output Encoding
- All database queries use PDO prepared statements.
- All user input is validated via `validate-input.php`.
- MI command output is rendered with `htmlspecialchars()` before display.
- CSRF tokens are required for all mutating operations.

### 3.5 Audit Logging
- All MI command executions are logged to `auth_audit_log`.
- All TLS reload attempts are logged.
- All dialplan/domain mutations are logged.
- Log entries include: timestamp, user, action, target, result.

### 3.6 Secret Hygiene
- No secrets are stored in PHP files.
- No MI command exposes credentials or private keys.
- TLS certificate paths are read from environment/config, not hardcoded.

---

## 4. Tool-Specific Security Notes

### 4.1 Dialog Viewer (web/dialog.php)
- **Read-only**: No mutation of active calls permitted.
- **Data exposed**: call-id, from_uri, to_uri, state, duration.
- **Risk**: Low — no mutating operations; data is operational, not PII.

### 4.2 MI Commands Runner (web/mi-commands.php)
- **Command injection mitigation**: Strict whitelist; no parameter injection.
- **Output sanitization**: `htmlspecialchars()` on all MI output.
- **Privilege separation**: Admin-only commands (`dlg_end_dlg`, `tls_reload`) are gated.

### 4.3 TLS Management (web/tls-management.php)
- **Cert display**: Read-only view of certificate metadata.
- **Reload trigger**: Admin-only; requires explicit confirmation.
- **Propagation**: Reload command sent via MI interface to OpenSIPS container.

### 4.4 Dialplan & Domains CRUD
- **SQL injection mitigation**: PDO prepared statements throughout.
- **CSRF protection**: All forms include CSRF tokens.
- **Validation**: Input validated via `validate-input.php`.

---

## 5. Data Classification

| Data Type | Classification | Tools | Notes |
|---|---|---|---|
| Active dialog metadata | Internal | Dialog Viewer | SIP URIs and IPs; no PII |
| MI command output | Internal | MI Commands | Runtime state; no secrets |
| Dialplan rules | Internal | Dialplan Manager | Routing logic; sensitive for security |
| Domain list | Internal | Domains Manager | Tenant enumeration |
| TLS cert metadata | Restricted | TLS Management | Expiry dates; no private keys |
| Statistics | Internal | Statistics Monitor | Performance metrics |

---

## 6. MSL Applicability Justification

**Determination**: **MSL-Relevant**

**Rationale**:
1. Dialog data contains SIP URIs and IP addresses that could reveal network topology.
2. MI command output exposes runtime state of the SIP proxy.
3. Unauthorized TLS reload could disrupt encrypted communications.
4. Dialplan rules reveal routing logic that could be exploited.

**Mitigations**:
- RBAC ensures only authorized personnel access these tools.
- Audit logging provides accountability.
- No PII or subscriber credentials are exposed.
- The tools are internal-facing only (no public access).

**Formal classification**: TSiSIP-SEC-020-MSL-RELEVANT-001

---

## 7. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Security Assessor | Security Governance | 2026-05-19 | Complete |
| Independent Reviewer | Architecture Review | 2026-05-19 | Approved |
| Security Owner | @b0yz4kr14 | 2026-05-19 | Approved |

**Governance Statement**: Feature 020 security assessment complete. All tools implement defense-in-depth with RBAC, whitelist, audit logging, and output encoding.
