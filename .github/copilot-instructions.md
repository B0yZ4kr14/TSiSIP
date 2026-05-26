# Copilot instructions for TSiSIP

## Repository state

This repository is a mature, documentation-first greenfield with foundation committed. All core source files, manifests, tests, build systems, and CI workflows are present and actively maintained.

## Build, Test, and Deploy Commands

### Canonical Build Commands
```bash
# Build the OpenSIPS image from source
docker build -t tsisip-opensips:latest .

# Validate rendered Compose configuration
docker compose config

# Validate OpenSIPS config syntax inside the built image
docker run --rm \
  -e DB_HOST=postgres -e DB_NAME=opensips -e DB_USER=opensips \
  -e HOST_PUBLIC_IP=127.0.0.1 -e OPENSIPS_LISTEN_IP=0.0.0.0 \
  -e RTPENGINE_HOST=rtpengine \
  -v $(pwd)/secrets/db_password:/run/secrets/db_password:ro \
  -v $(pwd)/secrets/auth_secret:/run/secrets/auth_secret:ro \
  -v $(pwd)/secrets/topology_secret:/run/secrets/topology_secret:ro \
  tsisip-opensips:latest \
  /entrypoint.sh /usr/local/sbin/opensips -c -f /etc/opensips/opensips.cfg

# Build all services
docker compose build

# Start the full stack
docker compose up -d
```

### Integration Tests
```bash
# Run the full integration suite
pytest tests/integration/ -v

# Individual test modules
pytest tests/integration/test_end_to_end_call.py -v
pytest tests/integration/test_rate_limiting.py -v
pytest tests/integration/test_ddos_protection.py -v
pytest tests/integration/test_anomaly_detection.py -v
pytest tests/integration/test_cdr_billing.py -v
```

### CI/CD
- GitHub Actions workflows: `.github/workflows/ci.yml`, `.github/workflows/deploy.yml`
- Trivy vulnerability scanning, SLSA provenance attestation, SBOM generation
- Secret leak detection in CI

### Project Structure
- `opensips/opensips.cfg.tpl` — OpenSIPS 3.6 LTS edge proxy configuration
- `docker-compose*.yml` — Docker Compose profiles (dev, prod, vps, monitoring)
- `db/init/` — PostgreSQL initialization scripts
- `docker/` — Container support files and Dockerfiles for all services
- `tests/` — Integration tests (pytest) and frontend tests
- `docs/` — Canonical architecture specification and runbooks
- `reports/` — Audit reports (brownfield, version-guard, memory-lint)

## Project context

TSiSIP is a Docker-image-first SIP edge-proxy stack built around OpenSIPS. The target architecture is:

- OpenSIPS as the single external SIP entry point on `5060/udp` and `5060/tcp`.
- RTP media relayed through RTPengine, the canonical media relay, with host-facing RTP on `10000-20000/udp`.
- Internal Asterisk PBX nodes isolated behind OpenSIPS with no direct external port publishing.
- PostgreSQL backing subscriber authentication and dynamic routing metadata.

Validate OpenSIPS-specific implementation details against the official OpenSIPS documentation for the selected LTS version before adding configs or docs.

## Expected architecture boundaries

- Keep OpenSIPS signaling, RTP relay, database, and Asterisk backend responsibilities separated.
- Treat Docker networking as part of the security boundary: backend PBX and database services should remain on internal networks unless a committed design explicitly changes this.
- OpenSIPS configuration should keep authentication, input validation, header-based routing, RTP relay engagement, and failover logic in clearly named route blocks.

## Project-specific conventions to preserve

- Treat Docker image delivery as canonical: OpenSIPS must be built and run from a committed, project-owned Dockerfile with runtime configuration injected through templates, environment variables, and secrets.
- Treat PostgreSQL as canonical: use `db_postgres`, PostgreSQL DSNs, and PostgreSQL DDL for authentication, routing, dispatcher, and custom TSiSIP metadata. PostgreSQL must not publish host ports and must be reachable only on the internal database network. Do not introduce MySQL/MariaDB variants unless the user explicitly changes the architecture.
- Store SIP Digest credentials as HA1 hashes, not plaintext passwords.
- Use only OpenSIPS modules, parameters, and functions validated against official OpenSIPS 3.6 LTS documentation. Do not add modules absent from OpenSIPS 3.6 LTS documentation, including Kamailio-only modules and `sanity`.
- For header-based routing, sanitize untrusted inbound headers before using routing metadata and avoid forwarding client credentials to backend PBX nodes.
- Keep generated/runtime secrets out of committed config; use Docker secrets or environment-substituted templates when container files are added.
- Reject legacy snippets that target OpenSIPS 3.4, `db_mysql`, bare-metal host installs, plaintext `subscriber.password` authentication, Kamailio `auth_check()`/`auth_challenge()`, hard-coded dispatcher set routing, or RTPengine loopback control in multi-container deployments.

## Canonical documentation orchestration

For requests that create or modify documentation, specifications, architecture, DevOps guidance, OpenSIPS configuration guidance, PostgreSQL schemas, or canonical project decisions, follow `docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md` before finalizing the work.

The required review model is simultaneous multi-agent validation when available, covering documentation forensics, OpenSIPS/RFC validation, solution architecture, DevOps documentation, data specifications, implementation specifications, and Socratic/Popperian falsifiability review.

<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan
<!-- SPECKIT END -->
