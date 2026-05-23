# Feature 008 Memory Synthesis: DevSecOps Deployment Automation

## Current Scope
Secure deployment to VPS TSiAPP via Ansible, Nginx proxy, secret discovery, and audit validation. Status: Complete.

## Relevant Decisions
- Ansible for idempotent server config with pre-flight checks.
- Nginx subdirectory routing (/TSiSIP/).
- vps-lite+PBX profile (7 services, ~3.8GB RAM).
- Build-on-target fallback when GHCR push fails.

## Active Architecture Constraints
- No secrets in logs; no_log: true.
- chmod 600 temp files.
- Deterministic image pinning; no :latest in production.

## Accepted Deviations
- SSL Labs grade B (A+ plan documented).
- Observability stack deferred to Phase 2.

## Relevant Security Constraints
- UFW, fail2ban, unattended-upgrades.
- TLS 1.2/1.3, HSTS, rate limiting.
- Deploy vs. operational secret scope separation.

## Related Historical Lessons
- GHCR permission denied -> build-on-target fallback.
- VPS load avg ~166 -> pre-flight load check (>50 abort).
- Build context mismatch -> per-service contexts.
- Postgres cap_drop: ALL failure -> added capabilities.

## Conflict Warnings
- Feature 009 formalizes this into a gated pipeline.

## Retrieval Notes
- Keywords: deploy, Ansible, Nginx, TSiAPP, reverse proxy, secret discovery.
- Related: 009, 001.
