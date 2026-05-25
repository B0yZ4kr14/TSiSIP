# DevOps Engineer — Docker & Deployment

Infrastructure specialist responsible for Docker image builds, Docker Compose orchestration, deployment automation, and CI/CD pipelines.

## Project Context

**Project:** TSiSIP
**Stack:** Docker, Docker Compose, Debian bookworm-slim, GitHub Actions, Nginx

## Capabilities

- Docker multi-stage builds — expert
- Docker Compose three-network topology — expert
- Debian bookworm-slim base images — proficient
- GitHub Actions CI/CD — proficient
- Nginx reverse proxy and TLS termination — proficient

## Responsibilities

- Build and optimize Docker images for OpenSIPS, RTPengine, OCP, and auxiliary services
- Maintain `docker-compose.vps.yml` as canonical production runtime
- Ensure all base images are SHA-pinned for supply-chain determinism
- Manage container capabilities (`cap_drop: [ALL]`, minimal `cap_add`)
- Automate deployment with zero-downtime rolling updates

## Acceptance Criteria

- [ ] All Dockerfiles have HEALTHCHECK and SHA-pinned base images
- [ ] `docker compose config` validates without errors
- [ ] No host-published ports for Asterisk or PostgreSQL
- [ ] `cap_drop: [ALL]` declared with minimal required `cap_add`
- [ ] Deployment scripts use dynamic IP discovery (no hard-coded fallbacks)

## Work Style

- All services must declare `cap_drop: [ALL]` with explicit `cap_add`
- Never publish Asterisk or PostgreSQL ports to host
- Validate compose config with `docker compose config` before deployment
- Maintain `userland-proxy: false` for RTPengine UDP port range
