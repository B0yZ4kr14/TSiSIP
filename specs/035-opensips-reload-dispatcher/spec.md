# Feature 035: OpenSIPS Runtime Reload & Dispatcher Management

## Objective

Deliver a zero-downtime dispatcher destination management interface within the TSiSIP OCP that allows operators to add, edit, remove, and reload dispatcher sets without restarting the OpenSIPS container.

## Background

The TSiSIP platform uses OpenSIPS `dispatcher` module for backend Asterisk routing. Currently:
- Dispatcher destinations are stored in the PostgreSQL `dispatcher` table
- OpenSIPS caches these destinations in memory at startup
- Adding or modifying a destination requires either:
  1. Restarting the OpenSIPS container (downtime), or
  2. Running `opensipsctl dispatcher add` followed by `ds_reload` via MI (manual, error-prone)

This feature provides a web UI for safe dispatcher CRUD with automatic `ds_reload` invocation, audit logging, and rollback capability.

## Scope

### In Scope
- CRUD for dispatcher destinations via OCP (admin/devops only)
- CRUD for dispatcher sets (grouping)
- One-click `ds_reload` via MI HTTP after changes
- Pre-reload validation: check destination SIP URI reachability (OPTIONS probe)
- Audit logging of all dispatcher changes
- Rollback to previous destination state (last 10 changes)
- Real-time destination health status (already in F034, enhanced here)
- Bulk import/export of dispatcher sets via CSV

### Out of Scope
- Modifying OpenSIPS .cfg file (use MI reload only)
- Dynamic module loading/unloading
- Changing dispatch algorithm (remains `4` — load-based with capacity check)
- HA/failover logic changes (handled by existing dispatcher module)

## Acceptance Criteria

| ID | Criterion | Verification |
|---|---|---|
| T35.1 | Admin can add a new dispatcher destination via OCP | UI test + DB verification |
| T35.2 | After adding, `ds_reload` is triggered automatically and destination appears in active set | MI `ds_list` verification |
| T35.3 | Pre-reload OPTIONS probe validates destination before adding | Test with invalid URI (should reject) |
| T35.4 | Audit log records who changed what and when | Query `ocp_audit_log` table |
| T35.5 | Rollback restores previous destination state | Add → Rollback → verify old state restored |
| T35.6 | Bulk CSV import adds 10+ destinations in one operation | Import test CSV |
| T35.7 | Role-based access: only admin/devops can modify dispatcher; readonly can view | Login with different roles |
| T35.8 | Graceful failure: if ds_reload fails, show error and do not commit DB change | Trigger invalid state and verify rollback |
| T35.9 | Dispatcher health widget updates within 5s of reload | SSE verification |
| T35.10 | Export produces valid CSV with all dispatcher columns | Download and parse CSV |

## Architecture

```
Admin OCP
    |
    +-- dispatcher.php (CRUD UI)
    |       +-- AJAX: /api/v1/dispatcher-crud.php
    |       +-- AJAX: /api/v1/dispatcher-reload.php
    |       +-- AJAX: /api/v1/dispatcher-rollback.php
    |       +-- AJAX: /api/v1/dispatcher-import.php
    |       +-- AJAX: /api/v1/dispatcher-export.php
    |
    +-- PostgreSQL (dispatcher table)
    |
    +-- OpenSIPS MI HTTP (ds_reload, ds_list, OPTIONS probe)
```

## Security

- All endpoints require admin or devops role
- CSRF token validation on all mutating requests
- Rate limit: max 5 reloads per minute per user
- Pre-reload OPTIONS probe runs from OCP container (not browser)
- Audit log is immutable (Feature 016 guarantee)
- Rollback only restores last 10 changes to prevent abuse

## Dependencies

- OpenSIPS MI HTTP module (already loaded)
- PostgreSQL dispatcher table (stock schema)
- OCP auth/session system (already in place)
- Audit log system (Feature 016)
- SSE stream (Feature 034) for real-time health updates

## Non-Goals

- Replace PostgreSQL with another DB for dispatcher storage
- Implement custom dispatch algorithms
- Modify OpenSIPS source code
- Multi-master dispatcher synchronization
