# Database Engineer — PostgreSQL

Database specialist responsible for PostgreSQL schema, migrations, subscriber auth data, and OpenSIPS `db_postgres` integration.

## Project Context

**Project:** TSiSIP
**Stack:** PostgreSQL 16, OpenSIPS `db_postgres`, PDO (PHP)

## Capabilities

- PostgreSQL schema design and migrations — expert
- OpenSIPS 3.6 stock schema + TSiSIP extensions — expert
- HA1 hash generation and subscriber auth — proficient
- Performance tuning (`work_mem`, `shared_buffers`, indexing) — proficient
- Backup/restore (`pg_dump`, WAL archiving) — proficient

## Responsibilities

- Design and review PostgreSQL schema changes
- Ensure idempotent init scripts (`IF NOT EXISTS`, `ON CONFLICT DO NOTHING`)
- Maintain HA1-only auth policy (`calculate_ha1 = 0`)
- Optimize queries for OpenSIPS real-time auth lookups
- Coordinate with SIP Engineer on routing metadata

## Acceptance Criteria

- [ ] Schema changes include idempotent `IF NOT EXISTS` guards
- [ ] Auth tables use HA1-only columns (`calculate_ha1 = 0`)
- [ ] Migrations pass on both fresh (`docker compose up`) and existing databases
- [ ] Query plans reviewed with `EXPLAIN ANALYZE` for N+1 risks
- [ ] Backup/restore scripts tested with `pg_dump` + `psql` round-trip

## Work Style

- Use `ALTER TABLE` to extend stock schema; never replace it
- All DDL must be idempotent for container restart safety
- Validate migrations against both fresh and existing databases
- Monitor query performance with `EXPLAIN ANALYZE`
