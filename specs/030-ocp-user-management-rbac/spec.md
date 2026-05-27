# Feature Specification: OCP User Management & RBAC

## Overview

**Feature**: OCP User Management & RBAC
**Short name**: ocp-user-management
**Created**: 2026-05-27
**Status**: In Progress

### Context

The TSiSIP OCP has role-based access control but lacks a dedicated admin UI for managing users. Admins must manually insert users into the database.

### Objective

1. Create user management page for admin CRUD operations
2. Enforce password policy
3. Add audit logging for all user mutations
4. Support self-service profile

---

## Functional Requirements

### FR-001: User List Page
- Paginated table with username, role, email, last login, status
- Search, filter, sort

### FR-002: Create User
- Form with username, email, role, password
- Username uniqueness validation
- Password complexity: min 8 chars, 1 upper, 1 lower, 1 digit
- Force password change on first login

### FR-003: Edit User
- Change role, email, reset password
- Enable/disable account
- Cannot edit own role

### FR-004: Delete User
- Confirmation modal
- Soft delete
- Cannot delete last admin or self

### FR-005: Password Policy
- bcrypt cost 12
- Password history (last 5)
- Expiration 90 days

### FR-006: Session Invalidation
- Clear sessions on disable/delete

### FR-007: Self-Service Profile
- Change password with verification
- Update email
- View login history

---

## Data Model

### ocp_users (existing)
- id, username, email, password_hash, role, force_password_change, is_active, created_at, updated_at, last_login, password_changed_at, deleted_at

### ocp_password_history (new)
- id, user_id FK, password_hash, changed_at

### ocp_user_sessions (new)
- id, user_id FK, session_token, ip_address, user_agent, created_at, last_activity, invalidated_at

---

## Success Criteria

| ID | Criterion | Target |
|---|---|---|
| SC-001 | User CRUD operations | 100% pass |
| SC-002 | Password policy enforcement | 100% pass |
| SC-003 | Audit log coverage | All mutations logged |
| SC-004 | Session invalidation | < 5s propagation |
