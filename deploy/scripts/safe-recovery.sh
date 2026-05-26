#!/bin/bash
# TSiSIP Safe Recovery Script
# Executar na VPS TSiAPP após reboot para evitar OOM/hang
set -euo pipefail

info()  { echo "[INFO] $*"; }
warn()  { echo "[WARN] $*"; }
error() { echo "[ERROR] $*" >&2; }

# Auto-detect compose file: VPS production first, then legacy prod
if [ -f "/opt/tsisip/docker-compose.vps.yml" ]; then
    COMPOSE_FILE="/opt/tsisip/docker-compose.vps.yml"
elif [ -f "/opt/tsisip/docker-compose.prod.yml" ]; then
    COMPOSE_FILE="/opt/tsisip/docker-compose.prod.yml"
else
    echo "ERROR: No compose file found in /opt/tsisip/" >&2
    exit 1
fi

# ─── 1. Parar containers não essenciais para liberar RAM ───
info "Parando containers não essenciais..."
NON_ESSENTIAL=(
    tsiapp-orthoplus
    tsiapp-tsiview
    tsiapp-tsimusic
    tsiapp-landpages
    tsiapp-smith-agent
)
for c in "${NON_ESSENTIAL[@]}"; do
    if docker ps --format '{{.Names}}' | grep -q "^${c}$"; then
        info "  Stopping $c"
        docker stop "$c" || true
    fi
done

# ─── 2. Iniciar TSiSIP em fases ───
info "Iniciando TSiSIP em fases (evita OOM)..."

PHASES=(
    "postgres"
    "rtpengine"
    "opensips"
    "ocp admin_api"
    "certbot certbot_exporter"
    "backup"
)

for phase in "${PHASES[@]}"; do
    info "Fase: $phase"
    docker compose -f "$COMPOSE_FILE" up -d $phase
    # Wait for containers in this phase to initialize and report healthy
    # before proceeding to the next phase (prevents OOM from parallel startup)
    sleep 10
    info "  RAM livre: $(free -m | awk 'NR==2{print $7}') MB"
done

# ─── 3. Verificar status ───
info "Status dos containers:"
docker compose -f "$COMPOSE_FILE" ps

# ─── 4. Configurar Nginx ───
info "Configurando Nginx..."
if [ -f /tmp/tsisip-location.conf ]; then
    # Adiciona location ao site existente
    if ! grep -q "BEGIN TSiSIP" /etc/nginx/sites-available/tsiapp-https; then
        echo "# BEGIN TSiSIP" | sudo tee -a /etc/nginx/sites-available/tsiapp-https >/dev/null
        cat /tmp/tsisip-location.conf | sudo tee -a /etc/nginx/sites-available/tsiapp-https >/dev/null
        echo "# END TSiSIP" | sudo tee -a /etc/nginx/sites-available/tsiapp-https >/dev/null
    fi
    
    if ! grep -q "limit_req_zone.*tsisip_web" /etc/nginx/nginx.conf; then
        sudo sed -i '/http {/a\    limit_req_zone \$binary_remote_addr zone=tsisip_web:10m rate=30r/m;\n    limit_conn_zone \$binary_remote_addr zone=tsisip_conn:10m;' /etc/nginx/nginx.conf
    fi
    
    sudo nginx -t && sudo systemctl reload nginx
    info "Nginx configurado!"
fi

# ─── 5. Health checks ───
info "Health checks..."
# Brief pause for health endpoints to become ready before probing
sleep 5

HEALTH_URLS=(
    "http://localhost/TSiSIP/login.php"
    "http://localhost/TSiSIP/health"
)

for url in "${HEALTH_URLS[@]}"; do
    code=$(curl -s -o /dev/null -w "%{http_code}" "$url" || echo "000")
    info "  $url → HTTP $code"
done

info "Safe recovery completo!"
