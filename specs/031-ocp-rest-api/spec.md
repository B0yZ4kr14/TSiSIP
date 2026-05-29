# Feature Specification: OCP REST API

## Overview

**Feature**: OCP REST API
**Short name**: ocp-rest-api
**Created**: 2026-05-27
**Status**: Complete

### Context

The TSiSIP OCP provides a comprehensive web UI for managing OpenSIPS, users, and system configuration. However, external systems (monitoring tools, CRMs, automation platforms) need programmatic access to TSiSIP data and controls.

### Objective

1. Expose read-only REST endpoints for system metrics and status
2. Expose authenticated REST endpoints for user and configuration management
3. Use API key authentication separate from session-based web auth
4. Provide OpenAPI/Swagger documentation

---

## Functional Requirements

### FR-031-001: API Key Management
- Admins can generate, revoke, and view API keys
- Keys are bcrypt-hashed in database
- Keys have optional expiration date
- Keys are scoped (read-only or read-write)

### FR-031-002: System Status Endpoint
- GET /api/v1/status — OpenSIPS, RTPengine, PostgreSQL health
- Returns JSON with status indicators

### FR-031-003: Metrics Endpoint
- GET /api/v1/metrics — Current MI statistics
- Returns JSON with dialogs, memory, processes, etc.

### FR-031-004: User Management Endpoints
- GET /api/v1/users — List users
- POST /api/v1/users — Create user
- PATCH /api/v1/users/:id — Update user
- DELETE /api/v1/users/:id — Soft delete user

### FR-031-005: Audit Log Endpoint
- GET /api/v1/audit — Query audit log
- Supports date range, action type, user filters

### FR-031-006: Error Handling
- Consistent JSON error responses
- Proper HTTP status codes
- Rate limiting (100 req/min per key)

---

## Security Requirements

| ID | Requirement |
|---|---|
| SR-001 | API keys transmitted in Authorization: Bearer header |
| SR-002 | HTTPS-only in production |
| SR-003 | Rate limiting per key |
| SR-004 | Audit log all API mutations |

## Success Criteria

| ID | Criterion | Target |
|---|---|---|
| SC-001 | All endpoints return valid JSON | 100% |
| SC-002 | API key auth works | 100% |
| SC-003 | Rate limiting enforced | < 0.1% bypass |
