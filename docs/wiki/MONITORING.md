# TSiSIP Monitoring Guide

## Overview

TSiSIP provides multiple monitoring capabilities.

## Health Check

### Endpoint
```
GET /health.php
```

### Response
```json
{
    "status": "healthy",
    "timestamp": "2026-05-27T03:44:13+00:00",
    "checks": {
        "database": {"status": "ok"},
        "opensips": {"status": "ok", "uptime": 3600}
    }
}
```

## System Health Page

- Component status
- Metrics cards
- Quick actions
- Auto-refresh

## Audit Log

- All actions logged
- Filter by user, action, date
- Export to CSV/JSON/TEXT

## Prometheus

### Metrics
- Request count
- Response time
- Error rate
- Active sessions

### Scraping
```yaml
scrape_configs:
  - job_name: 'tsisip'
    static_configs:
      - targets: ['localhost:8080']
```

## Grafana

### Dashboards
- System overview
- SIP metrics
- Database metrics
- Application metrics

### Alerts
- High error rate
- High latency
- Service down
- Disk full

## Logs

### Docker Logs
```bash
docker compose logs -f
```

### System Logs
View via System Logs page.

### Audit Logs
View via Audit Log page.

## Alerts

### Email
Configure SMTP in environment.

### Webhook
Send to Slack, PagerDuty, etc.

### Thresholds
- CPU > 80%
- Memory > 90%
- Disk > 80%
- Error rate > 5%

## Tools

### Built-in
- Health endpoint
- System Health page
- Audit Log
- System Logs

### External
- Prometheus
- Grafana
- Alertmanager
- ELK Stack (future)

## Best Practices

1. Monitor all components
2. Set up alerting
3. Review logs regularly
4. Keep dashboards updated
5. Test alerts
6. Document runbooks

## Runbooks

### OpenSIPS Down
1. Check container status
2. Review logs
3. Restart if needed
4. Verify health

### Database Connection Failed
1. Check PostgreSQL status
2. Verify credentials
3. Check network
4. Restart if needed

### High Error Rate
1. Check application logs
2. Review recent changes
3. Check dependencies
4. Rollback if needed
