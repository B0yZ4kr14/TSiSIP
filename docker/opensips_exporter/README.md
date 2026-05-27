# OpenSIPS Prometheus Exporter

Exports OpenSIPS MI (Management Interface) metrics to Prometheus.

## Metrics

- `opensips_dispatcher_target_response_ms`
- `opensips_auth_failures_total`
- `opensips_dialogs_active`
- `opensips_memory_used_bytes`

## Build

```bash
docker build -t tsisip/opensips_exporter:latest -f docker/opensips_exporter/Dockerfile .
```

## Configuration

Connects to OpenSIPS MI HTTP on port 8888.
