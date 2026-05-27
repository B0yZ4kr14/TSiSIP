# TSiSIP Deployment Guide

## Prerequisites

- Ubuntu 22.04 LTS or newer
- Docker 24.0+
- Docker Compose 2.20+
- 4GB RAM minimum
- 20GB disk space
- Internet access for images

## Quick Start

```bash
# Clone repository
git clone https://github.com/B0yZ4kr14/TSiSIP.git
cd TSiSIP

# Copy environment
cp .env.example .env

# Set secrets
mkdir -p secrets
echo "your-db-password" > secrets/db_password
echo "your-auth-secret" > secrets/auth_secret
echo "your-topology-secret" > secrets/topology_secret

# Build and start
docker compose build
docker compose up -d

# Verify
curl http://localhost/health.php
```

## Production Deployment

### 1. Server Preparation
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
```

### 2. SSL/TLS
```bash
# Using Let's Encrypt
sudo apt install certbot
sudo certbot certonly --standalone -d tsiapp.io

# Or use provided certificates
cp /path/to/cert.pem secrets/server.crt
cp /path/to/key.pem secrets/server.key
```

### 3. Environment Configuration
```bash
# Edit .env
HOST_PUBLIC_IP=179.190.15.116
OPENSIPS_LISTEN_IP=0.0.0.0
DB_HOST=postgres
DB_NAME=opensips
DB_USER=opensips
```

### 4. Database Setup
```bash
# Schema initialization
docker compose up -d postgres
docker compose exec postgres psql -U opensips -d opensips -f /docker-entrypoint-initdb.d/01-stock-opensips-schema.sql
docker compose exec postgres psql -U opensips -d opensips -f /docker-entrypoint-initdb.d/02-tsisip-extensions.sql
docker compose exec postgres psql -U opensips -d opensips -f /docker-entrypoint-initdb.d/03-seed-data.sql
```

### 5. Start Services
```bash
docker compose up -d
```

### 6. Verify
```bash
# Health check
curl -f http://localhost/health.php

# Login test
curl -X POST http://localhost/login.php -d "username=admin&password=admin123"
```

## Reverse Proxy (Nginx)

```nginx
server {
    listen 443 ssl http2;
    server_name tsiapp.io;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## Firewall

```bash
# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTPS
sudo ufw allow 443/tcp

# Allow SIP (if needed)
sudo ufw allow 5060/udp
sudo ufw allow 5060/tcp

# Enable
sudo ufw enable
```

## Monitoring

### Prometheus
```yaml
scrape_configs:
  - job_name: 'tsisip'
    static_configs:
      - targets: ['localhost:8080']
```

### Grafana
Import dashboard from `docker/grafana/dashboards/`.

## Backup Strategy

### Automated
```bash
# Add to crontab
0 2 * * * /path/to/scripts/backup-db.sh
```

### Manual
```bash
./scripts/backup-db.sh
```

## Updates

### Rolling Update
```bash
# Pull latest
git pull origin main

# Rebuild
docker compose build

# Restart
docker compose up -d
```

### Database Migrations
```bash
# Run new migrations
for f in db/init/*.sql; do
    docker compose exec postgres psql -U opensips -d opensips -f "$f"
done
```

## Rollback

```bash
# Stop services
docker compose down

# Restore database
./scripts/restore-db.sh backups/tsisip_db_YYYYMMDD_HHMMSS.sql.gz

# Start services
docker compose up -d
```

## Troubleshooting

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

## Support

- GitHub Issues
- Email: devops@tsiapp.io
- Documentation: https://tsiapp.io/help.php
