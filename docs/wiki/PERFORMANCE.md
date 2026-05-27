# TSiSIP Performance Guide

## Benchmarks

### Page Load Times
| Page | Desktop | Mobile |
|------|---------|--------|
| Dashboard | < 500ms | < 800ms |
| Login | < 300ms | < 500ms |
| System Health | < 600ms | < 1s |

### API Response Times
| Endpoint | Target |
|----------|--------|
| Health | < 100ms |
| MI Proxy | < 200ms |
| SSE Stream | < 50ms |

## Optimization

### Database
- Add indexes on frequently queried columns
- Use prepared statements
- Connection pooling
- Regular VACUUM

### Cache
- MI response cache (5s TTL)
- Page cache (60s TTL)
- OPcache enabled
- Asset manifest for cache-busting

### Frontend
- Minified CSS/JS
- Lazy loading
- Compressed images
- CDN (future)

### Network
- HTTP/2
- Gzip compression
- Keep-alive connections
- SSE instead of polling

## Monitoring

### Key Metrics
- Request latency
- Error rate
- Cache hit rate
- Database query time
- Memory usage
- CPU usage

### Tools
- Prometheus
- Grafana
- Health endpoint
- Audit logs

## Tuning

### PHP
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
```

### PostgreSQL
```sql
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
```

### Docker
```yaml
deploy:
  resources:
    limits:
      memory: 512M
```

## Load Testing

```bash
# Using ab
ab -n 1000 -c 10 http://localhost/health.php

# Using wrk
wrk -t4 -c100 -d30s http://localhost/health.php
```

## Scaling

### Vertical
- More CPU/RAM
- Faster disk (SSD)
- Network bandwidth

### Horizontal
- Multiple OpenSIPS instances
- Database read replicas
- Load balancer
- Container orchestration
