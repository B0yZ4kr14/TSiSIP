# TSiSIP Docker Guide

## Images

### OpenSIPS
- Base: debian:bookworm-slim
- Source build
- Ports: 5060/udp, 5060/tcp

### PostgreSQL
- Base: postgres:15
- Persistent volume
- Port: internal only

### RTPengine
- Base: debian:bookworm-slim
- Ports: 10000-20000/udp

### OCP
- Base: php:8.2-apache
- Port: 80/tcp
- Volumes: web/, secrets/

## Networks

### sip_edge
- OpenSIPS, RTPengine
- External access

### sip_internal
- OpenSIPS, RTPengine, Asterisk
- No external

### db_internal
- OpenSIPS, PostgreSQL
- No external

## Volumes

### postgres_data
- Database files
- Persistent

### logs
- Application logs
- Shared

### backups
- Database backups
- Shared

## Commands

### Build
```bash
docker compose build
```

### Start
```bash
docker compose up -d
```

### Stop
```bash
docker compose down
```

### Logs
```bash
docker compose logs -f
```

### Exec
```bash
docker compose exec <service> <command>
```

### Stats
```bash
docker stats
```

## Dockerfile

### Best Practices
- Multi-stage builds
- Minimal base images
- Non-root user
- Health checks
- Layer caching

### Security
- Read-only filesystems
- Drop capabilities
- No new privileges
- Secrets management

## Compose

### Profiles
- dev: Development
- prod: Production
- vps: VPS deployment
- monitoring: Metrics

### Scaling
```yaml
deploy:
  replicas: 2
```

## Troubleshooting

### Container Won't Start
```bash
docker compose logs <service>
docker inspect <container>
```

### High Memory
```bash
docker stats
```

### Network Issues
```bash
docker network inspect tsisip_sip_edge
```

## Resources

- [Docker Docs](https://docs.docker.com)
- [Compose Spec](https://compose-spec.io)
- [Dockerfile Reference](https://docs.docker.com/engine/reference/builder/)
