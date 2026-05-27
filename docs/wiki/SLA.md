# TSiSIP SLA

## Uptime

### Target
99.9% uptime

### Measurement
Monthly

### Calculation
(Uptime / Total) * 100

## Response Time

### Support
| Severity | Response |
|----------|----------|
| P1 | 1 hour |
| P2 | 4 hours |
| P3 | 24 hours |
| P4 | 72 hours |

### API
| Endpoint | Target |
|----------|--------|
| Health | 100ms |
| Login | 200ms |
| Dashboard | 500ms |

## Availability

### Hours
- Business: 24/7
- Support: 24/7

### Holidays
- Reduced support
- Emergency only

## Compensation

### Downtime
| Duration | Credit |
|----------|--------|
| < 1 hour | None |
| 1-4 hours | 10% |
| 4-8 hours | 25% |
| > 8 hours | 50% |

## Exclusions

- Scheduled maintenance
- Force majeure
- Customer error
- Third party

## Contact

sla@tsiapp.io
