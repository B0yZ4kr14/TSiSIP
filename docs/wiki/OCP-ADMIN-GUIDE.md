# TSiSIP Control Panel — Admin Guide

## Installation

### Requirements
- Docker 24.0+
- Docker Compose 2.20+
- PostgreSQL 15+
- OpenSIPS 3.6 LTS

### Setup
```bash
# Build images
docker compose build

# Start database
docker compose up -d postgres

# Run migrations
docker compose exec postgres psql -U opensips -d opensips -f /docker-entrypoint-initdb.d/07-user-preferences.sql
docker compose exec postgres psql -U opensips -d opensips -f /docker-entrypoint-initdb.d/08-user-bookmarks.sql

# Start all services
docker compose up -d

# Verify
curl http://localhost/health.php
```

## Configuration

### Environment Variables
| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | `postgres` | PostgreSQL host |
| `DB_NAME` | `opensips` | Database name |
| `DB_USER` | `opensips` | Database user |
| `OPENSIPS_MI_URL` | `http://opensips:8888/mi` | MI HTTP endpoint |
| `RTPENGINE_HOST` | `rtpengine` | RTPengine host |

### Secrets
Place in `secrets/` directory:
- `db_password` — Database password
- `auth_secret` — SIP auth secret
- `topology_secret` — Topology hiding secret

## User Management

### Creating Users
```sql
INSERT INTO ocp_users (username, email, password_hash, role, created_by)
VALUES ('newuser', 'user@tsiapp.io', crypt('temppass', gen_salt('bf')), 'readonly', 1);
```

### Roles
| Role | Level | Access |
|------|-------|--------|
| `readonly` | 1 | View only |
| `operator` | 2 | Basic operations |
| `devops` | 3 | System management |
| `admin` | 5 | Full access |

### Password Policy
- Minimum 12 characters
- Must contain uppercase, lowercase, number
- bcrypt hashing
- Force change on first login (optional)

## Monitoring

### Health Checks
- `/health.php` — Public JSON status
- `/system-health.php` — Detailed component status
- MI HTTP — OpenSIPS internal state

### Metrics
- Active dialogs
- Subscriber counts
- Login attempts
- Audit events
- Gateway status

### Alerts
Configure Prometheus Alertmanager for:
- High failed login rate
- OpenSIPS unreachable
- Database connection failures
- RTPengine errors

## Backup

### Database
```bash
docker compose exec postgres pg_dump -U opensips opensips > backup.sql
```

### Configuration
```bash
tar czf config-backup.tar.gz web/ db/ secrets/
```

## Troubleshooting

### OpenSIPS MI Unreachable
1. Check OpenSIPS container: `docker compose ps opensips`
2. Verify MI module loaded
3. Check network connectivity

### Database Connection Failed
1. Check PostgreSQL container
2. Verify credentials in secrets/
3. Check connection string

### High Memory Usage
1. Check MI cache size
2. Review SSE connections
3. Monitor active sessions

### Slow Page Loads
1. Check MI response times
2. Verify cache hit rate
3. Review database indexes

## Security Hardening

### HTTPS
- Configure reverse proxy with TLS
- Set `cookie_secure=1` in PHP
- Use HSTS headers

### Firewall
- Block port 5060 except from trusted sources
- Restrict MI HTTP to internal network
- Limit database access

### Audit
- Review audit logs regularly
- Monitor for brute force attempts
- Check for unauthorized access

## Performance Tuning

### Database
- Add indexes on frequently queried columns
- Monitor query performance
- Vacuum and analyze regularly

### Cache
- Adjust MI cache TTL (default 5s)
- Enable OPcache in PHP
- Use asset manifest for cache-busting

### SSE
- Limit concurrent connections
- Adjust heartbeat interval
- Monitor connection health

## Maintenance

### Log Rotation
```bash
# Configure logrotate for OCP logs
/var/log/tsisip/*.log {
    daily
    rotate 30
    compress
    delaycompress
}
```

### Updates
1. Pull latest images
2. Run migrations
3. Verify functionality
4. Monitor for errors

### Cleanup
- Remove old audit logs (> 90 days)
- Clear expired sessions
- Purge old export files

## Support

For issues:
1. Check logs: `docker compose logs ocp`
2. Review audit log
3. Check health endpoint
4. Contact devops team
