#!/usr/bin/env bash
#
# Teste local do perfil vps-lite
# Valida se o docker-compose.vps.yml sobe corretamente em ambiente local.
#
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.vps.yml"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info()  { echo -e "${GREEN}[TEST]${NC} $*"; }
log_warn()  { echo -e "${YELLOW}[TEST]${NC} $*"; }
log_fatal() { echo -e "${RED}[TEST]${NC} $*" >&2; exit 1; }

log_info "=== Teste Local VPS-Lite ==="

# Preparar ambiente de teste
mkdir -p "${PROJECT_ROOT}/secrets"

# Gerar secrets de teste
openssl rand -base64 24 | head -c 32 > "${PROJECT_ROOT}/secrets/auth_secret"
openssl rand -base64 24 | head -c 16 > "${PROJECT_ROOT}/secrets/db_password"
openssl rand -base64 24 | head -c 32 > "${PROJECT_ROOT}/secrets/topology_secret"
openssl rand -base64 48 > "${PROJECT_ROOT}/secrets/backup_encryption_key"

# Certificados dummy para teste
openssl req -x509 -newkey rsa:2048 -keyout "${PROJECT_ROOT}/secrets/server.key" \
    -out "${PROJECT_ROOT}/secrets/server.crt" -days 1 -nodes \
    -subj "/CN=localhost" 2>/dev/null
cp "${PROJECT_ROOT}/secrets/server.crt" "${PROJECT_ROOT}/secrets/ca.crt"
touch "${PROJECT_ROOT}/secrets/crl.pem"

# Discover Docker network IPs dynamically
discover_network_ip() {
    local network="$1"
    local ip
    ip=$(docker network inspect "${network}" --format='{{range .IPAM.Config}}{{.Gateway}}{{end}}' 2>/dev/null || true)
    if [[ -z "${ip}" ]]; then
        echo "[ERROR] Failed to discover IP for Docker network: ${network}" >&2
        exit 1
    fi
    echo "${ip}"
}

RTPENGINE_PRIVATE_IP=$(discover_network_ip "tsisip_sip_edge")
RTPENGINE_INTERNAL_IP=$(discover_network_ip "tsisip_sip_internal")

# .env de teste
cat > "${PROJECT_ROOT}/.env" <<ENVEOF
OPENSIPS_LISTEN_IP=0.0.0.0
HOST_PUBLIC_IP=127.0.0.1
RTPENGINE_PRIVATE_IP=${RTPENGINE_PRIVATE_IP}
RTPENGINE_INTERNAL_IP=${RTPENGINE_INTERNAL_IP}
ENVEOF

# Subir stack
log_info "Subindo stack..."
docker compose -f "${COMPOSE_FILE}" --env-file "${PROJECT_ROOT}/.env" up -d

# Aguardar PostgreSQL
log_info "Aguardando PostgreSQL..."
for i in {1..30}; do
    if docker compose -f "${COMPOSE_FILE}" ps postgres | grep -q "healthy"; then
        log_info "PostgreSQL OK."
        break
    fi
    # Poll PostgreSQL health every 2 seconds until healthy or timeout
    sleep 2
done

# Verificar OpenSIPS
log_info "Verificando OpenSIPS..."
for i in {1..30}; do
    if docker compose -f "${COMPOSE_FILE}" ps opensips | grep -q "healthy"; then
        log_info "OpenSIPS OK."
        break
    fi
    # Poll OpenSIPS health every 2 seconds until healthy or timeout
    sleep 2
done

# Verificar OCP
log_info "Verificando OCP..."
for i in {1..30}; do
    if curl -sf http://localhost:8084/login.php >/dev/null 2>&1; then
        log_info "OCP responde em :8084."
        break
    fi
    # Poll OCP endpoint every 2 seconds until responsive or timeout
    sleep 2
done

# Verificar modulos TLS no OpenSIPS
log_info "Verificando modulos TLS..."
if docker compose -f "${COMPOSE_FILE}" exec -T opensips ls /usr/local/lib64/opensips/modules/ | grep -q "tls_openssl"; then
    log_info "tls_openssl.so presente."
else
    log_warn "tls_openssl.so AUSENTE."
fi

# Stats
log_info "=== Status Final ==="
docker compose -f "${COMPOSE_FILE}" ps --format 'table {{.Name}}\t{{.Status}}\t{{.Ports}}'

log_info "=== Teste concluido ==="
log_warn "Para derrubar: docker compose -f ${COMPOSE_FILE} down -v"
