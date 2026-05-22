# Feature 006: SIP-Layer Rate Limiting & DDoS Protection

## Overview

Protects TSiSIP from SIP-layer abuse through per-source IP throttling, subscriber auth rate limits, dispatcher failover, dynamic ban lists, and traffic anomaly detection.

## Components

| Component | Technology | Purpose |
|-----------|-----------|---------|
| IP Throttling | OpenSIPS pike | Per-source IP request rate limiting |
| Auth Limits | OpenSIPS htable + auth_db | Per-subscriber auth failure tracking |
| Load Balancing | OpenSIPS dispatcher | Capacity-based routing |
| Ban Lists | OpenSIPS htable | Dynamic IP/URI blocking with TTL |
| Anomaly Detection | Python sidecar | Statistical traffic analysis |

## Architecture

```
Internet -> OpenSIPS (5060/udp+tcp)
              |-- pike: IP rate limiting
              |-- htable: auth failure counters, ban lists
              |-- dispatcher: load-based routing
              +-- event_route -> anomaly-detector (port 8080)
```

## Configuration

### OpenSIPS Modules

- pike.so: Per-IP request throttling
  - sampling_time_unit: 2 seconds
  - reqs_density_per_unit: 50 requests
  - remove_latency: 10 seconds

- htable.so: In-memory hash tables
  - auth_failures: 1024 entries, autoexpire 3600s
  - ban_list: 4096 entries, autoexpire 86400s
  - trunk_whitelist: 256 entries, no expiration

### Rate Limiting Behavior

| Scenario | Threshold | Action |
|----------|-----------|--------|
| Per-IP flood | 50 req / 2s | Silent drop (UDP) or 429 (TCP/TLS) |
| Auth failures | 10 / 60s per user | 403 Forbidden, 5min ban |
| Trunk bypass | Trusted IPs | Skip pike check |

### TCP Anti-Slowloris

- tcp_max_connections: 4096
- tcp_connection_lifetime: 300s
- tcp_read_timeout: 30s

## Ban Management

### MI Commands

```bash
# View ban list
opensipsctl fifo htable_dump ban_list

# Remove ban
opensipsctl fifo htable_delete ban_list <ip_or_user>

# View auth failures
opensipsctl fifo htable_dump auth_failures
```

### Ban Sources

- pike: Automatic from rate limiting
- auth: From auth failure threshold
- manual: Operator-initiated
- anomaly: From anomaly detector

## Anomaly Detection

The anomaly-detector sidecar:

- Consumes events from OpenSIPS
- Maintains 24-hour statistical baseline
- Triggers alerts when Z-score > 3.0
- Exposes metrics on port 8080

### Metrics

| Metric | Description |
|--------|-------------|
| tsisip_current_rps | Current requests per second |
| tsisip_baseline_mean_rps | Baseline mean RPS |
| tsisip_baseline_stddev_rps | Baseline stddev RPS |
| tsisip_anomaly_z_score | Current Z-score |
| tsisip_anomaly_alerts_total | Total alerts triggered |

## Testing

```bash
# Run integration tests
pytest tests/integration/test_rate_limiting.py -v

# Manual ban test
opensipsctl fifo htable_dump ban_list
```

## Files

- opensips/opensips.cfg.tpl - Rate limiting routes and module config
- docker/anomaly-detector/Dockerfile - Anomaly detection container
- docker/anomaly-detector/detector.py - Detection engine
- docker/anomaly-detector/baseline.py - Statistical baseline
- tests/integration/test_rate_limiting.py - Integration tests
