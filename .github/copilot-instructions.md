# Copilot instructions for TSiSIP

## Repository state

This repository is currently greenfield: no source files, manifests, README, test configuration, or build system are present yet. Do not invent build, test, lint, or single-test commands until the corresponding tooling exists in the repository.

When project files are introduced, update this file with the exact commands from the committed manifests, such as `package.json`, `pyproject.toml`, `Makefile`, `docker-compose.yml`, or CI workflows.

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
