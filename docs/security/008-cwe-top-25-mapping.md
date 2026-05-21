# CWE Top 25 Mapping

**Date**: 2026-05-21
**Scope**: TSiSIP codebase (OpenSIPS C, PHP OCP, Python, Bash)
**Assessor**: Architecture Guard + Security Governance preset

## Memory-Unsafe Language Justification

OpenSIPS 3.6 LTS is implemented in C (memory-unsafe). TSiSIP does not modify OpenSIPS source; it uses the official upstream binary compiled inside a Docker image. Mitigation:
- Base image (`debian:bookworm-slim`) receives security updates.
- Trivy CI scan detects CVEs in the OpenSIPS binary and base image.
- No custom C code is written by the TSiSIP project.

## CWE Mapping

| CWE | Name | Status | Evidence |
|---|---|---|---|
| CWE-787 | Out-of-bounds Write | N/A | No custom C code; upstream OpenSIPS responsibility |
| CWE-79 | Cross-site Scripting (XSS) | Partial | htmlspecialchars used in some places; audit needed |
| CWE-89 | SQL Injection | Met | PDO prepared statements everywhere |
| CWE-352 | Cross-Site Request Forgery | Partial | CSRF on dispatcher, subscribers, trunk forms; **missing on change-password.php** |
| CWE-22 | Path Traversal | Met | No file uploads; all includes use __DIR__ prefix |
| CWE-125 | Out-of-bounds Read | N/A | No custom C code |
| CWE-78 | OS Command Injection | Met | No shell_exec/system/backticks in PHP; all shell scripts use quoted variables |
| CWE-416 | Use After Free | N/A | No custom C code |
| CWE-862 | Missing Authorization | Met | Role hierarchy enforced in OCP; OpenSIPS auth for all non-OPTIONS |
| CWE-434 | Unrestricted File Upload | N/A | No file upload functionality |
| CWE-94 | Code Injection | Met | No eval/assert/dynamic include in PHP |
| CWE-20 | Improper Input Validation | Partial | X-Route-Key validated in OpenSIPS; PHP input validation present but not exhaustive |
| CWE-77 | Command Injection | Met | Same as CWE-78 |
| CWE-200 | Information Exposure | Partial | No stack traces in production; debug mode controlled by env var |
| CWE-287 | Improper Authentication | Met | bcrypt for OCP; HA1 digest for SIP |
| CWE-269 | Improper Privilege Management | Met | cap_drop ALL; minimal cap_add per service |
| CWE-502 | Deserialization | N/A | No serialized data from untrusted sources |
| CWE-264 | Permissions, Privileges, and Access Controls | Met | Docker security_opt no-new-privileges |
| CWE-476 | NULL Pointer Dereference | N/A | No custom C code |
| CWE-798 | Hardcoded Credentials | Met | No hardcoded secrets; all injected via Docker secrets |
| CWE-918 | Server-Side Request Forgery (SSRF) | Met | No user-controlled URLs in backend requests |
| CWE-306 | Missing Authentication | Met | All non-OPTIONS SIP requests require auth |
| CWE-319 | Cleartext Transmission | Met | TLS 1.2+ mandatory; SRTP for media |
| CWE-400 | Uncontrolled Resource Consumption | Partial | pike rate limiting present; no global rate limit on OCP admin panel |
| CWE-611 | Improper Restriction of XML External Entity Reference | N/A | No XML parsing in codebase |

## Actionable Findings

1. **CWE-352 (CSRF)**: Add CSRF to change-password.php.
2. **CWE-20 (Input Validation)**: Audit all PHP endpoints for missing validation on numeric IDs and enums.
3. **CWE-400 (Resource Consumption)**: Add rate limiting to OCP admin login endpoint.

## References
- CWE Top 25 2023: https://cwe.mitre.org/top25/
- security_constitution.md
- architecture_constitution.md
