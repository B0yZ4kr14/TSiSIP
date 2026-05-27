# TSiSIP Configuration Guide

## Environment Variables

### Required
| Variable | Description | Default |
|----------|-------------|---------|
| DB_HOST | PostgreSQL host | postgres |
| DB_NAME | Database name | opensips |
| DB_USER | Database user | opensips |
| HOST_PUBLIC_IP | Public IP | 127.0.0.1 |

### Optional
| Variable | Description | Default |
|----------|-------------|---------|
| OPENSIPS_LISTEN_IP | OpenSIPS IP | 0.0.0.0 |
| RTPENGINE_HOST | RTPengine host | rtpengine |
| OPENSIPS_MI_URL | MI URL | http://opensips:8888/mi |

## Secrets

### Files
- `secrets/db_password`
- `secrets/auth_secret`
- `secrets/topology_secret`

### Generation
```bash
openssl rand -base64 32 > secrets/db_password
```

## PHP Configuration

### php.ini
```ini
session.cookie_secure=1
session.cookie_httponly=1
session.cookie_samesite=Strict
opcache.enable=1
```

## Database

### Connection
```
pgsql:host=postgres;dbname=opensips;port=5432
```

### Pooling
- Persistent connections
- Prepared statements
- Transaction support

## Docker

### Networks
- sip_edge: Public
- sip_internal: Private
- db_internal: Database

### Volumes
- postgres_data
- logs
- backups

## Nginx

### Reverse Proxy
```nginx
location / {
    proxy_pass http://localhost:8080;
    proxy_set_header Host $host;
}
```

## SSL/TLS

### Let's Encrypt
```bash
certbot certonly --standalone -d tsiapp.io
```

### Self-signed
```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout secrets/server.key \
    -out secrets/server.crt
```

## Customization

### Theme
- CSS variables
- Dark mode
- Presets

### Logo
- Replace `web/tsisip/assets/logo.png`
- Update asset manifest

### Colors
- Edit `web/tsisip/css/tsisip-theme.css`
- Use CSS custom properties

## Troubleshooting

### Check Config
```bash
docker compose config
```

### Validate
```bash
bash scripts/lint.sh
```

### Logs
```bash
docker compose logs -f
```
