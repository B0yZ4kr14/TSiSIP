# Tasks: OCP User Management & RBAC

## Phase 1: Database Schema

- [x] T001: Create db/init/08-user-management-schema.sql
  - ocp_password_history table
  - ocp_user_sessions table
  - Indexes and foreign keys

## Phase 2: Password Policy Library

- [x] T002: Create web/common/password-policy.php
  - validatePassword(string $pwd): array{valid: bool, errors: string[]}
  - isPasswordInHistory(int $userId, string $pwd): bool
  - hashPassword(string $pwd): string (bcrypt cost 12)
  - isPasswordExpired(int $userId): bool

## Phase 3: User List Page

- [x] T003: Create web/users.php
  - Admin-only access check
  - Paginated table from ocp_users (excluding deleted)
  - Search by username/email
  - Filter by role and is_active
  - Sort columns
  - "Add User" button linking to user-edit.php

## Phase 4: Create/Edit User

- [x] T004: Create web/user-edit.php
  - Form: username, email, role, password, confirm_password, force_password_change, is_active
  - Server-side validation
  - CSRF protection
  - On create: insert user, log audit
  - On edit: update fields, log audit, invalidate sessions if disabled
  - Prevent self-role-change and last-admin-deletion

## Phase 5: Delete User

- [x] T005: Create web/user-delete.php
  - POST endpoint with user_id and CSRF token
  - Soft delete (set deleted_at)
  - Prevent self-delete and last-admin-delete
  - Invalidate sessions
  - Log audit

## Phase 6: Self-Service Profile

- [x] T006: Update web/profile.php
  - Display user info
  - Change password form (current + new + confirm)
  - Update email form
  - Login history table (last 10 from audit log)

## Phase 7: Session Invalidation

- [x] T007: Update web/common/config.php
  - On login: insert into ocp_user_sessions
  - On each request: check if session invalidated
  - On logout: invalidate session record

## Phase 8: Navigation & Integration

- [x] T008: Add "Users" link to role-nav.php (admin only)
- [x] T009: Add "Users" to dashboard system links

## Phase 9: Tests

- [x] T010: Create tests/integration/test-ocp-users.sh
  - CRUD operations via curl
  - Password policy validation
  - Session invalidation check
- [x] T011: Run test-ocp-all.sh and fix regressions

## Phase 10: Build & Commit

- [x] T012: docker compose build ocp
- [x] T013: Commit with conventional commits
