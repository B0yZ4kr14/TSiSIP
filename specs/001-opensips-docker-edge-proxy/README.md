# Feature: TSiSIP SIP Edge Foundation

## Quick Start

1. Create runtime secrets:
   ```bash
   mkdir -p secrets
   echo 'your-db-password' > secrets/db_password
   echo '0123456789abcdef0123456789abcdef' > secrets/auth_secret
   echo 'your-topology-secret' > secrets/topology_secret
   ```

2. Build the TSiSIP SIP edge image:
   ```bash
   docker build -t tsisip/opensips:latest .
   ```

3. Start the stack:
   ```bash
   docker compose up -d
   ```

4. Validate syntax:
   ```bash
   docker compose exec opensips opensips -c -f /etc/opensips/opensips.cfg
   ```

## Architecture

- `opensips/`: TSiSIP SIP edge configuration template and related OpenSIPS engine files
- `docker/`: Container definitions for OpenSIPS, RTPengine, and Asterisk
- `db/init/`: PostgreSQL initialization scripts
- `secrets/`: Runtime secrets (excluded from git)

## Acceptance Criteria

See `spec.md`, `plan.md`, and `tasks.md` for full details.

**Last Updated**: 2026-05-19
