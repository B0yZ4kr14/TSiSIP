# Orchestrated Implementation Plan: All Remaining Steps

**Mode**: OMK Orchestrated  
**Scope**: MemoryLint ML-001–004, SEC-ACTION-005, Critique Review C5  
**Objective**: Zero outstanding MEDIUM/LOW items from audit reports.

---

## Phase 1 — Memory Reservations (ML-001)
**Target**: docker-compose.yml
**Action**: Add `deploy.resources.reservations.memory` to certbot (128M), tailscale-cert (64M), certbot-exporter (32M).

## Phase 2 — PHP Memory Config (ML-002)
**Target**: docker/ocp/php.ini (new file)
**Action**: Create php.ini with memory_limit=256M, max_execution_time=30, opcache.memory_consumption=64.

## Phase 3 — Backup Memory Limits (ML-003)
**Target**: docker/backup/backup.sh
**Action**: Add `nice -n 10` to gzip, document memory bounds.

## Phase 4 — OpenSIPS pkg_mem Docs (ML-004)
**Target**: opensips/opensips.cfg.tpl
**Action**: Add comment documenting pkg_mem_size calculation.

## Phase 5 — Threat Model (SEC-ACTION-005)
**Target**: docs/security/threat-model.md (new file)
**Action**: Create STRIDE-based threat model for SIP edge.

## Phase 6 — Constitution Reference Template (C5)
**Target**: .specify/templates/constitution-reference.md (new file)
**Action**: Create template for spec authors to reference constitution.

---

## Validation Gates
- After each phase: run `docker compose config` if compose changed
- After Phase 2: verify php.ini is copied in Dockerfile
- After all phases: run FR-ID validation + doctor
