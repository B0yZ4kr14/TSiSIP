# Tasks: OCP REST API

## Phase 1: Schema & Infrastructure

- [x] T001: Create db/init/09-api-keys-schema.sql
- [x] T002: Create web/api/common/auth.php
- [x] T003: Create web/api/common/rate-limit.php

## Phase 2: Router & Middleware

- [x] T004: Create web/api/index.php router
- [x] T005: Implement Bearer token auth middleware
- [x] T006: Implement JSON error handler

## Phase 3: Endpoints

- [x] T007: GET /api/v1/status
- [x] T008: GET /api/v1/metrics
- [x] T009: GET /api/v1/users
- [x] T010: POST /api/v1/users
- [x] T011: PATCH /api/v1/users/:id
- [x] T012: DELETE /api/v1/users/:id
- [x] T013: GET /api/v1/audit

## Phase 4: API Key Management UI

- [x] T014: Add API Keys page to OCP (admin only)
- [x] T015: Generate/regenerate/revoke key functionality

## Phase 5: Documentation

- [x] T016: Create web/api-docs.php with OpenAPI spec
- [x] T017: Add navigation link to API docs

## Phase 6: Tests

- [x] T018: Create tests/integration/test-api.sh
- [x] T019: Verify rate limiting
- [x] T020: Verify auth rejection

## Phase 7: Build & Commit

- [x] T021: docker compose build ocp
- [x] T022: Commit with conventional commits
