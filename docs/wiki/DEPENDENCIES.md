# TSiSIP Dependencies

## System

### Required
| Package | Version | Purpose |
|---------|---------|---------|
| Docker | 24.0+ | Containerization |
| Docker Compose | 2.20+ | Orchestration |
| PostgreSQL | 15+ | Database |
| OpenSIPS | 3.6 LTS | SIP Proxy |

### Optional
| Package | Version | Purpose |
|---------|---------|---------|
| Nginx | 1.24+ | Reverse proxy |
| Certbot | 2.0+ | SSL certificates |
| Prometheus | 2.40+ | Metrics |
| Grafana | 9.0+ | Dashboards |

## PHP

### Core
| Extension | Purpose |
|-----------|---------|
| pdo_pgsql | PostgreSQL |
| session | Sessions |
| json | JSON |
| curl | HTTP |
| mbstring | Multibyte |
| openssl | Crypto |

### Development
| Tool | Purpose |
|------|---------|
| xdebug | Debugging |
| phpunit | Testing |
| phpstan | Static analysis |

## JavaScript

### Runtime
| Package | Version | Purpose |
|---------|---------|---------|
| None | - | Vanilla JS |

### Future
| Package | Purpose |
|---------|---------|
| D3.js | Charts |
| Chart.js | Graphs |

## CSS

### None
- Pure CSS
- Custom properties
- No frameworks

## Docker

### Images
| Image | Version | Purpose |
|-------|---------|---------|
| debian | bookworm | Base |
| postgres | 15 | Database |
| php | 8.2-apache | Web |

## Security

### Tools
| Tool | Purpose |
|------|---------|
| openssl | TLS/SSL |
| bcrypt | Passwords |
| CSRF tokens | Protection |

## Monitoring

### Tools
| Tool | Purpose |
|------|---------|
| Prometheus | Metrics |
| Grafana | Dashboards |
| Alertmanager | Alerts |

## Testing

### Tools
| Tool | Purpose |
|------|---------|
| pytest | Python tests |
| curl | HTTP tests |
| ab | Load tests |

## Documentation

### Tools
| Tool | Purpose |
|------|---------|
| Markdown | Docs |
| Mermaid | Diagrams |

## Updates

### Check
```bash
bash scripts/version-guard.sh
```

### Security
```bash
docker scan <image>
```

## License Compatibility

All dependencies are open-source compatible.

### Permissive
- MIT
- BSD
- Apache 2.0

### Copyleft
- GPL (not used)

## Vulnerabilities

### Scanning
```bash
docker scan tsisip-opensips
```

### Reporting
Contact security@tsiapp.io
