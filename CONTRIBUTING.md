# Contributing to TSiSIP

Thank you for contributing to TSiSIP. This document provides the minimal necessary guidance to ensure every change aligns with the project's architecture, security model, and governance standards.

## Prerequisites

- Docker Engine 24.0+ with Compose V2
- `make` (GNU Make)
- Git 2.40+
- Python 3.11+ (for test scripts and exporter)
- Node.js 20+ (for OCP frontend builds only)

## Project Structure

| Directory | Purpose |
|---|---|
| `opensips/` | OpenSIPS 3.6 LTS configuration templates |
| `docker/` | Dockerfiles, entrypoints, and container support files |
| `db/init/` | PostgreSQL schema initialization scripts |
| `web/` | OCP PHP frontend (administrative control panel) |
| `deploy/` | Ansible playbooks, nginx configs, VPS scripts |
| `docs/` | Canonical architecture specs, runbooks, security evidence |
| `specs/` | Feature specifications (spec.md, plan.md, tasks.md) |
| `scripts/` | Build, test, and operational helper scripts |
| `secrets/` | Runtime sensitive data (`.gitignore` protected — never commit) |

## Getting Started

```bash
# Clone and enter the repository
git clone <repo-url> && cd TSiSIP

# Copy environment template
cp .env.example .env

# Build all images
docker compose build

# Validate configuration
docker compose config

# Run the full stack
docker compose up -d

# Verify runtime health
make test
```

## Development Workflow

### 1. Specification-Driven Changes

**All non-trivial changes require a specification.**

Create or update the relevant feature directory under `specs/NNN-feature-name/`:

- `spec.md` — What, why, non-goals, acceptance criteria
- `plan.md` — Architecture decisions, phases, dependency graph
- `tasks.md` — Actionable, traceable, dependency-ordered tasks

Reference existing specs (e.g., `specs/020-ocp-critical-tool-gap-closure/`) for format.

### 2. Constitution Gates

Every implementation plan must pass these gates before code changes:

| Gate | Validation |
|---|---|
| Docker-first | No bare-metal or VM-first runtime paths |
| PostgreSQL-only | No `db_mysql`, MySQL, or MariaDB references |
| Module validity | Only OpenSIPS 3.6 LTS documented modules |
| Sensitive-data hygiene | No plaintext auth material in proposed changes |
| Network isolation | Asterisk and PostgreSQL have no host-published ports |

### 3. Code Style

- **PHP**: 4-space indent, PDO prepared statements for all DB access, `htmlspecialchars()` for output
- **Python**: 4-space indent, type hints encouraged, `ruff` for linting
- **Shell**: 4-space indent, `set -eu`, prefer `[[ ]]` over `[ ]` for Bash
- **YAML/JSON**: 2-space indent
- **SQL**: 4-space indent, uppercase keywords, `snake_case` identifiers
- **OpenSIPS config**: 4-space indent, integer algorithm args for dispatcher

The repository includes an `.editorconfig`. Please ensure your editor respects it.

### 4. Security Requirements

- All admin tools require `requireRole('devops')` minimum
- Mutating operations require CSRF token validation
- All database queries use PDO prepared statements
- SIP auth stores HA1 hashes only (`calculate_ha1 = 0`); never plaintext
- Sensitive runtime data lives in `secrets/` directory only; verify with `git diff --name-only` before every commit
- Run `make lint` before committing to catch auth material leakage patterns

### 5. Testing

```bash
# OpenSIPS config syntax validation
docker run --rm tsisip-opensips:latest opensips -c -f /etc/opensips/opensips.cfg

# Docker Compose configuration validation
docker compose config

# SIP runtime tests (requires stack running)
make test

# Options probe
sipsak -s sip:opensips:5060 -vv

# INVITE auth challenge test
python3 scripts/test-invite-407.py
```

New OpenSIPS config changes **must** pass `opensips -c`.
New Docker images **must** pass healthchecks before deployment.

### 6. Commit Convention

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(020): add dialog viewer with read-only dialog table access
fix(backup): correct pg_isready healthcheck command
refactor(opensips): replace htable with cachedb_local
docs(runbook): update TLS certificate rotation procedures
```

Scope should reference the feature number when applicable.

### 7. Documentation

- Architecture decisions belong in `docs/TSiSIP-CANONICAL-SPEC.md`
- Security evidence belongs in `docs/security/evidence/[feature-dir]/`
- Operator procedures belong in `docs/TSiSIP-OPERATOR-RUNBOOK.md`
- Changes to canonical spec require multi-agent validation per `docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md`

## Review Process

- P0 findings (security, auth contract, topology leaks) block release until resolved
- Non-blocking architecture drift becomes tracked refactor work in `.specify/memory/`
- All changes affecting `docs/TSiSIP-CANONICAL-SPEC.md` require explicit approval

## Brownfield Hygiene

Before starting work on an existing feature area:

```bash
# Run brownfield scan against canonical spec and AGENTS.md
make brownfield

# Address any HIGH or MEDIUM findings before adding new code
```

Each remediation cycle must include:
1. The fix
2. Evidence in `evidence/remediation/[cycle-dir]/`
3. Post-fix validation scan

## Communication

- Open an issue for bugs or feature requests
- Reference the feature spec directory in issue descriptions
- Tag security-sensitive issues with `security` label

---

*Last updated: 2026-05-19*
