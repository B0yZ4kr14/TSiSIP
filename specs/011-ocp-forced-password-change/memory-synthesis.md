# Feature 011 Memory Synthesis: OCP Forced Passphrase Change & Session Security

## Current Scope
Mandatory passphrase change on first login with PHP session cookie hardening and HTTPS proxy detection.

## Relevant Decisions
- OCP bcrypt separate from SIP HA1.
- 12-char complexity minimum.
- force_password_change flag + checkPasswordChange() guard.
- Cron inside OCP container.

## Active Architecture Constraints
- PHP 8.2, PDO PostgreSQL.
- Secure/HttpOnly/SameSite=Strict cookies.
- HTTPS via X-Forwarded-Proto.
- No MFA, expiration, or email reset.

## Accepted Deviations
None.

## Relevant Security Constraints
- Default admin must change passphrase before access.
- Nginx must forward X-Forwarded-Proto.
- Guard required on all protected pages.

## Related Historical Lessons
- Missing X-Forwarded-Proto keeps cookie_secure off.
- Guard pattern is simple to enforce across pages.

## Conflict Warnings
- Feature 016 audits password changes.
- New protected pages must include guard.

## Retrieval Notes
- Keywords: forced password change, session security, PHP cookie, checkPasswordChange.
- Related: 002, 010, 016.
