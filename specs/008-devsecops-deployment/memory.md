# Feature 008 Memory: DevSecOps Deployment Automation

## Current Scope
Secure, repeatable deployment pipeline for TSiSIP to VPS TSiAPP using Ansible, Nginx reverse proxy, secret discovery scripts, Socratic architecture audit, and Popper falsification tests. Status: Complete.

## Relevant Decisions
- **Ansible for orchestration**: Idempotent server configuration with pre-flight checks (disk, Docker daemon, registry reachability).
- **Nginx reverse proxy with subdirectory routing**: https://tsiapp.io/TSiSIP proxies to OCP container on localhost:8084.
- **vps-lite+PBX profile**: 7 services targeting ~3.8 GB RAM as the production baseline.
- **Build-on-target fallback**: When GHCR push fails, images are built directly on the VPS using docker-compose.build.yml.

## Active Architecture Constraints
- No secrets logged in stdout, stderr, or Ansible logs; no_log: true on sensitive tasks.
- Temporary files use chmod 600 with explicit deletion reminders.
- Deterministic image pinning enforced (git short-SHA tags); :latest is CI artifact only.
- Only images in tsisip/ or prom/ namespaces are allowed in Compose files.

## Accepted Deviations
- SSL Labs grade B evidenced (A+ remediation plan documented) — real certificates required for A+.
- Full observability stack (Prometheus, Grafana, Alertmanager) deferred to Phase 2.
- Upstream/provider firewall management outside VPS host is out of scope.

## Relevant Security Constraints
- UFW, fail2ban, unattended-upgrades on VPS.
- TLS 1.2/1.3, HSTS, security headers, rate limiting (30r/m with burst=10).
- Secret scope separation: deploy secrets (GitHub, SSH) vs. operational secrets (vault, DB password).
- DNS and SSL certificate generation assumed present (not managed by this feature).

## Related Historical Lessons
- GHCR push permission denied incident (GITHUB_TOKEN lacking cross-repo package write) led to build-on-target fallback and artifact transfer mode.
- VPS critical load incident (load avg ~166) led to pre-flight load check; abort deploy if load avg >50.
- Docker Compose build context mismatch (incorrect context paths) resolved by per-service contexts in docker-compose.build.yml.
- Postgres container failed under cap_drop: ALL; added CHOWN, SETUID, SETGID, DAC_OVERRIDE.

## Conflict Warnings
- Feature 009 formalizes the deploy pipeline into a structured, gated process that builds on these scripts.

## Retrieval Notes
- Search terms: deploy, Ansible, Nginx, TSiAPP, TSiHomeLab, DevSecOps, reverse proxy, secret discovery, vps-lite.
- Related features: 009 (deploy pipeline formalization), 001 (container images).
