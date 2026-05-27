# Tasks: OCP REST API

## Phase 1: Schema & Infrastructure

- [ ] T001: Create db/init/09-api-keys-schema.sql
- [ ] T002: Create web/api/common/auth.php
- [ ] T003: Create web/api/common/rate-limit.php

## Phase 2: Router & Middleware

- [ ] T004: Create web/api/index.php router
- [ ] T005: Implement Bearer token auth middleware
- [ ] T006: Implement JSON error handler

## Phase 3: Endpoints

- [ ] T007: GET /api/v1/status
- [ ] T008: GET /api/v1/metrics
- [ ] T009: GET /api/v1/users
- [ ] T010: POST /api/v1/users
- [ ] T011: PATCH /api/v1/users/:id
- [ ] T012: DELETE /api/v1/users/:id
- [ ] T013: GET /api/v1/audit

## Phase 4: API Key Management UI

- [ ] T014: Add API Keys page to OCP (admin only)
- [ ] T015: Generate/regenerate/revoke key functionality

## Phase 5: Documentation

- [ ] T016: Create web/api-docs.php with OpenAPI spec
- [ ] T017: Add navigation link to API docs

## Phase 6: Tests

- [ ] T018: Create tests/integration/test-api.sh
- [ ] T019: Verify rate limiting
- [ ] T020: Verify auth rejection

## Phase 7: Build & Commit

- [ ] T021: docker compose build ocp
- [ ] T022: Commit with conventional commits
