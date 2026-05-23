# Feature 011 Memory: OCP Forced Passphrase Change & Session Security

## Current Scope
Enforce mandatory passphrase change on first OCP login and harden PHP session cookies against XSS, CSRF, and session fixation with HTTPS detection behind the Nginx reverse proxy.

## Relevant Decisions
- **Separate password schemes**: OCP uses bcrypt password_hash(); SIP auth uses HA1 hashes only. Never conflate the two.
- **12-character complexity**: Minimum length with uppercase, lowercase, number, and symbol.
- **force_password_change flag on ocp_users**: BOOLEAN DEFAULT TRUE; cleared on successful change.
- **checkPasswordChange() guard**: Called after requireAuth() on all protected pages; redirects to change-password.php if flag is set.
- **Cron inside OCP container**: Daily cron job at 03:17 for audit retention aligns with existing architecture; /var/log/tsisip owned by www-data.

## Active Architecture Constraints
- PHP 8.2 with PDO for PostgreSQL.
- Session security: Secure, HttpOnly, SameSite=Strict, use_strict_mode=1.
- HTTPS detection via X-Forwarded-Proto from Nginx.
- No MFA, no expiration policy, no email reset.

## Accepted Deviations
- None.

## Relevant Security Constraints
- Default admin account must rotate predictable passphrase before any other access.
- session.cookie_secure enabled dynamically when HTTPS is detected.
- Nginx must forward X-Forwarded-Proto for PHP to detect HTTPS correctly.
- All protected pages must call checkPasswordChange() after requireAuth().

## Related Historical Lessons
- HTTPS detection behind reverse proxy requires explicit header forwarding; missing it causes cookie_secure to remain off.
- The guard pattern (requireAuth() then checkPasswordChange()) is simple and enforceable across all protected pages.

## Conflict Warnings
- Feature 016 (Audit Log) instruments PASSWORD_CHANGE events; ensure the audit hook captures both success and failure cases.
- All protected pages (dashboard, dispatcher, rtpengine, wiki) must include the guard; new pages must follow the same pattern.

## Retrieval Notes
- Search terms: forced password change, session security, PHP cookie, X-Forwarded-Proto, checkPasswordChange, bcrypt.
- Related features: 002 (OCP auth foundation), 010 (navigation), 016 (audit logging of password changes).
