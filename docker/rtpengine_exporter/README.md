# RTPengine Prometheus Exporter

Exports RTPengine statistics to Prometheus.

## Metrics

- `rtpengine_up`
- `rtpengine_sessions_active`
- `rtpengine_calls_total`

## Build

```bash
docker build -t tsisip/rtpengine_exporter:latest -f docker/rtpengine_exporter/Dockerfile .
```

## Configuration

Connects to RTPengine control socket on port 22222.
