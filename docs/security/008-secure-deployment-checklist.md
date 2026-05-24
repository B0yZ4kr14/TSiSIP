# Secure Deployment Checklist — TSiSIP vps-lite

**Date**: 2026-05-23
**Environment**: Production (tsiapp.io)

---

## Pre-Deployment

- [ ] DNS A record configured (`tsiapp.io` → `179.190.15.116`)
- [ ] Secrets/ directory populated (all 12 files present)
- [ ] .env file complete and validated against .env.example
- [ ] Docker images pulled from GHCR (`:test` tag verified)
- [ ] Backup volume exists and has sufficient space

## Deployment

- [ ] `docker compose -f docker-compose.vps.yml up -d` executes without errors
- [ ] All services report `healthy` status within 2 minutes
- [ ] PostgreSQL schema initialized (\dt shows expected tables)
- [ ] OpenSIPS config validates (`opensips -c` passes)
- [ ] SIP OPTIONS returns 200 OK from edge
- [ ] OCP responds on http://127.0.0.1:8084/login.php

## Post-Deployment Security Verification

- [ ] Port scan confirms zero Asterisk/PostgreSQL exposure
- [ ] TLS certificate valid and auto-rotation configured
- [ ] HSTS headers present on HTTPS responses
- [ ] auth_audit_log table receiving events
- [ ] No secrets in container logs or evidence files

## Rollback Readiness

- [ ] Rollback runbook reviewed and executable
- [ ] Volume backup completed before changes
- [ ] Abort triggers defined and communicated
