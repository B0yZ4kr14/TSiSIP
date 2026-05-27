# PgBouncer Connection Pooler

PostgreSQL connection pooler for OpenSIPS and OCP.

## Features

- Transaction pooling
- SCRAM-SHA-256 authentication
- Reduces PostgreSQL connection overhead

## Build

```bash
docker build -t tsisip/pgbouncer:latest -f docker/pgbouncer/Dockerfile .
```

## Configuration

- Pool size: 100 connections
- Default database: opensips
- Auth file: mounted from secrets
