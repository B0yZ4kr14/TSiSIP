# Certbot Prometheus Exporter

Exports TLS certificate expiry metrics to Prometheus.

## Metrics

- `certbot_certificate_expiry_timestamp`
- `certbot_certificate_days_until_expiry`

## Build

```bash
docker build -t tsisip/certbot_exporter:latest -f docker/certbot_exporter/Dockerfile .
```
