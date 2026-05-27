# TSiSIP Troubleshooting Guide

## Login Issues

### "Invalid credentials"
- Verify username/password
- Check caps lock
- Account may be locked after 5 failed attempts
- Wait 15 minutes or contact admin

### "Session expired"
- Re-login
- Check browser cookies are enabled
- Verify HTTPS connection

## Page Errors

### 403 Forbidden
- Insufficient permissions
- Contact admin for role upgrade

### 404 Not Found
- Page may have moved
- Check URL
- Use navigation menu

### 500 Internal Server Error
- Check system logs: `docker compose logs ocp`
- Verify database connectivity
- Restart OCP container

## Component Issues

### OpenSIPS MI Unreachable
```bash
docker compose ps opensips
docker compose logs opensips
curl http://opensips:8888/mi
```

### Database Connection Failed
```bash
docker compose ps postgres
docker compose exec postgres pg_isready -U opensips
```

### RTPengine Not Responding
```bash
docker compose ps rtpengine
docker compose logs rtpengine
```

## Performance

### Slow Page Loads
- Check MI cache hit rate
- Verify database indexes
- Monitor SSE connections

### High Memory Usage
- Check active sessions
- Review audit log size
- Clear page cache

### High CPU
- Check for runaway processes
- Review MI call frequency
- Monitor container stats

## Data Issues

### Missing Data
- Check time range filters
- Verify database has data
- Check for deleted records

### Incorrect Statistics
- Refresh page
- Check cache TTL
- Verify MI responses

## Network

### Cannot Reach OCP
- Check firewall rules
- Verify port 80/443 open
- Check reverse proxy config

### WebSocket/SSE Fails
- Verify EventSource support
- Check network connectivity
- Review CSP headers

## Container Issues

### Container Won't Start
```bash
docker compose down
docker compose up -d
docker compose logs <service>
```

### Disk Full
```bash
df -h
docker system prune -f
docker volume prune -f
```

### Memory Issues
```bash
free -h
docker stats
```

## Logs

### View OCP Logs
```bash
docker compose logs -f ocp
```

### View All Logs
```bash
docker compose logs -f
```

### Export Logs
```bash
docker compose logs > tsisip-logs-$(date +%Y%m%d).txt
```

## Recovery

### Reset Admin Password
```bash
docker compose exec postgres psql -U opensips -d opensips -c "
UPDATE ocp_users SET password_hash = crypt('newpass', gen_salt('bf')) WHERE username = 'admin';
"
```

### Clear All Sessions
```bash
docker compose exec ocp rm -rf /var/lib/php/sessions/*
```

### Restore Database
```bash
./scripts/restore-db.sh backups/tsisip_db_YYYYMMDD_HHMMSS.sql.gz
```

## Support

If issues persist:
1. Collect logs
2. Check health endpoint
3. Review audit log
4. Contact devops team
