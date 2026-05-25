# Squad Team

> TSiSIP — Docker-image-first SIP edge-proxy platform

## Coordinator

| Name | Role | Notes |
|------|------|-------|
| Squad | Coordinator | Routes work, enforces handoffs and reviewer gates. |

## Members

| Name | Role | Charter | Status |
|------|------|---------|--------|
| sip-engineer | OpenSIPS & Media Relay | .squad/agents/sip-engineer/charter.md | active |
| database-engineer | PostgreSQL | .squad/agents/database-engineer/charter.md | active |
| frontend-engineer | OCP PHP | .squad/agents/frontend-engineer/charter.md | active |
| devops-engineer | Docker & Deployment | .squad/agents/devops-engineer/charter.md | active |
| security-engineer | Auth, TLS, Audit | .squad/agents/security-engineer/charter.md | active |
| qa-engineer | Testing & Validation | .squad/agents/qa-engineer/charter.md | active |
| scribe | Documentation | .squad/agents/scribe/charter.md | active |
| ralph | Persistent Memory | .squad/agents/ralph/charter.md | active |

## Project Context

- **Project:** TSiSIP
- **Created:** 2026-05-24
- **Primary Stack:** OpenSIPS 3.6 LTS + PostgreSQL 16 + RTPengine + Asterisk + PHP 8.2
- **Runtime:** Docker Compose (three-network topology)
