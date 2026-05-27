# Plan: OCP User Management & RBAC

## Architecture

- PHP pages with PDO PostgreSQL backend
- Server-side rendering with minimal JS
- Reuse existing auth system (config.php, csrf.php)
- Reuse existing audit system (logAuditEvent)

## Files

- web/users.php — User list (admin only)
- web/user-edit.php — Create/Edit user (admin only)
- web/user-delete.php — Soft delete endpoint (admin only)
- web/profile.php — Self-service profile (all authenticated)
- web/common/password-policy.php — Password validation library
- db/init/08-user-management-schema.sql — New tables

## Password Flow

1. Input password -> validate complexity -> bcrypt hash (cost 12)
2. Store hash -> log to password_history -> update password_changed_at
3. On login -> check expiration -> redirect if forced change needed

## Session Invalidation

- Store active sessions in ocp_user_sessions
- On disable: UPDATE invalidated_at = NOW() WHERE user_id = X
- On each request: check if session invalidated
