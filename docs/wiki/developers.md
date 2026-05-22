# Developer Guide

## Development Principles

- Preserve the TSiSIP SIP engine baseline (OpenSIPS 3.6 LTS).
- Preserve PostgreSQL as the only database.
- Keep Asterisk and PostgreSQL private.
- Prefer existing scripts, specs, and Docker Compose patterns.
- Update docs when implementation changes operational behavior.

## Fast Validation

```bash
docker compose -f docker-compose.vps.yml config
bash scripts/ci-scan.sh
bash -n docker/backup/*.sh deploy/scripts/*.sh
```

## GitNexus Workflow

Run from the repository root:

```bash
npx gitnexus status
npx gitnexus analyze
```

Use the GitNexus MCP tools for targeted work:

- `query`: discover related symbols and flows.
- `context`: inspect one symbol or method.
- `impact`: check blast radius before edits.
- `detect_changes`: review affected flows after edits.

## Test Focus

High-value tests and checks:

- `tests/integration/test_backup_restore.py`
- `tests/integration/test_observability.py`
- `tests/integration/test_rate_limiting.py`
- `scripts/ci-scan.sh`
- `deploy/validate.sh`

## Documentation Targets

When changing runtime behavior, update:

- `STATUS.md`
- `deploy/VPS-DEPLOY-READINESS.md`
- `docs/TSiSIP-OPERATOR-RUNBOOK.md`
- `reports/vps-production-validation-2026-05-19.md`
- Relevant `specs/<feature>/spec.md`, `plan.md`, and `tasks.md`
