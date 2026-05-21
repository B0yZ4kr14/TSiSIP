#!/usr/bin/env bash
#
# TSiSIP VPS Deploy — Arquitetura DevSecOps
# Perfil: vps-lite (5 servicos, <2GB RAM alocado)
# Decisao autonoma: nao subir stack completo em 4GB RAM.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.vps.yml"
SECRETS_DIR="${PROJECT_ROOT}/secrets"
ENV_FILE="${PROJECT_ROOT}/.env"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
log_fatal() { echo -e "${RED}[FATAL]${NC} $*" >&2; exit 1; }

log_info "=== TSiSIP VPS Deploy — Profile: vps-lite ==="

if [[ $EUID -ne 0 ]]; then
    log_warn "Nao executando como root. Algumas operacoes podem falhar."
fi

if ! docker version >/dev/null 2>&1; then
    log_fatal "Docker daemon nao esta acessivel."
fi

DISK_AVAIL_GB=$(df -BG "${PROJECT_ROOT}" | awk 'NR==2 {gsub(/G/,""); print $4}')
if [[ "${DISK_AVAIL_GB}" -lt 5 ]]; then
    log_fatal "Espaco em disco insuficiente: ${DISK_AVAIL_GB}GB (minimo 5GB)."
fi
log_info "Disco disponivel: ${DISK_AVAIL_GB}GB"

RAM_MB=$(free -m | awk '/^Mem:/ {print $7}')
if [[ "${RAM_MB}" -lt 1024 ]]; then
    log_warn "RAM disponivel baixa: ${RAM_MB}MB."
else
    log_info "RAM disponivel: ${RAM_MB}MB"
fi

if [[ ! -d "${SECRETS_DIR}" ]]; then
    log_fatal "Diretorio de secrets nao encontrado: ${SECRETS_DIR}"
fi

AUTH_SECRET="${SECRETS_DIR}/auth_secret"
if [[ -f "${AUTH_SECRET}" ]]; then
    AUTH_LEN=$(wc -c < "${AUTH_SECRET}" | tr -d ' ')
    if [[ "${AUTH_LEN}" -ne 32 ]]; then
        log_warn "auth_secret tem ${AUTH_LEN} bytes (requer 32). Gerando novo..."
        openssl rand -base64 24 | head -c 32 > "${AUTH_SECRET}"
        log_info "Novo auth_secret gerado (32 bytes)."
    else
        log_info "auth_secret OK (32 bytes)."
    fi
else
    log_warn "auth_secret nao encontrado. Gerando..."
    openssl rand -base64 24 | head -c 32 > "${AUTH_SECRET}"
    chmod 600 "${AUTH_SECRET}"
    log_info "auth_secret gerado."
fi

BACKUP_KEY="${SECRETS_DIR}/backup_encryption_key"
if [[ -f "${BACKUP_KEY}" ]]; then
    KEY_LEN=$(wc -c < "${BACKUP_KEY}" | tr -d ' ')
    if [[ "${KEY_LEN}" -lt 32 ]]; then
        log_warn "backup_encryption_key curta (${KEY_LEN} bytes). Gerando nova..."
        openssl rand -base64 48 > "${BACKUP_KEY}"
    fi
else
    log_warn "backup_encryption_key nao encontrada. Gerando..."
    openssl rand -base64 48 > "${BACKUP_KEY}"
    chmod 600 "${BACKUP_KEY}"
fi

log_info "Verificando containers legados..."
NON_CRITICAL="orthoplus tsiview tsimusic landpages smith"
for c in ${NON_CRITICAL}; do
    if docker ps --format '{{.Names}}' | grep -iq "^${c}"; then
        log_warn "Parando container nao-critico: ${c}"
        docker stop "${c}" 2>/dev/null || true
    fi
done

if [[ ! -f "${ENV_FILE}" ]]; then
    log_warn ".env nao encontrado. Criando com defaults..."
    cat > "${ENV_FILE}" <<'EOF'
OPENSIPS_LISTEN_IP=0.0.0.0
HOST_PUBLIC_IP=127.0.0.1
RTPENGINE_PRIVATE_IP=172.19.0.1
RTPENGINE_INTERNAL_IP=172.21.0.1
EOF
fi

if grep -q 'RTPENGINE_INTERNAL_IP=10\.0\.0\.2' "${ENV_FILE}" 2>/dev/null; then
    # Discover the actual Docker network gateway for sip_internal
    RTPENGINE_INTERNAL_IP=$(docker network inspect tsisip_sip_internal --format='{{range .IPAM.Config}}{{.Gateway}}{{end}}' 2>/dev/null || echo "172.21.0.1")
    if grep -q "^RTPENGINE_INTERNAL_IP=" "${ENV_FILE}" 2>/dev/null; then
        sed -i "s|^RTPENGINE_INTERNAL_IP=.*|RTPENGINE_INTERNAL_IP=${RTPENGINE_INTERNAL_IP}|" "${ENV_FILE}"
    else
        echo "RTPENGINE_INTERNAL_IP=${RTPENGINE_INTERNAL_IP}" >> "${ENV_FILE}"
    fi
    log_warn "Ajustado RTPENGINE_INTERNAL_IP para ${RTPENGINE_INTERNAL_IP} (descoberto da rede Docker)"
fi

log_info "Verificando login no GHCR..."
if ! docker info 2>/dev/null | grep -q "ghcr.io"; then
    if [[ -n "${GITHUB_TOKEN:-}" ]]; then
        echo "${GITHUB_TOKEN}" | docker login ghcr.io -u "${GITHUB_USER:-b0yz4kr14}" --password-stdin >/dev/null 2>&1
        log_info "Login GHCR realizado."
    else
        log_warn "GITHUB_TOKEN nao definido. Pulando docker login."
    fi
fi

log_info "=== Wave 1: PostgreSQL + RTPengine ==="
docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" pull postgres rtpengine 2>&1 | tail -5
docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" up -d postgres rtpengine

log_info "Aguardando PostgreSQL healthy..."
for i in {1..30}; do
    if docker compose -f "${COMPOSE_FILE}" ps postgres | grep -q "healthy"; then
        log_info "PostgreSQL healthy."
        break
    fi
    sleep 2
done

if ! docker compose -f "${COMPOSE_FILE}" ps postgres | grep -q "healthy"; then
    log_fatal "PostgreSQL nao ficou healthy. Verifique logs."
fi

# WAL archiving writes into /backup/wal as the `postgres` user inside the container.
# The shared volume is often root-owned on first boot; enforce safe ownership/permissions.
log_info "Garantindo permissoes do WAL archive em /backup/wal..."
docker compose -f "${COMPOSE_FILE}" exec -T --user root postgres sh -lc \
  'mkdir -p /backup/wal /backup/daily /backup/metrics /backup/validate && chown -R postgres:postgres /backup/wal && chmod 750 /backup/wal'

if docker compose -f "${COMPOSE_FILE}" exec -T postgres psql -U opensips -d opensips -c "SELECT COUNT(*) FROM subscriber;" >/dev/null 2>&1; then
    log_info "Schema PostgreSQL OK."
else
    log_warn "Schema incompleto. Aplicando scripts de init..."
    for sql in "${PROJECT_ROOT}/db/init/"*.sql; do
        if [[ -f "${sql}" ]]; then
            docker compose -f "${COMPOSE_FILE}" exec -T postgres psql -U opensips -d opensips < "${sql}" || log_warn "Falha ao aplicar ${sql}"
        fi
    done
fi

log_info "=== Wave 2: OpenSIPS + OCP ==="
docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" pull opensips ocp 2>&1 | tail -5
docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" up -d opensips ocp

log_info "Aguardando OpenSIPS healthy..."
for i in {1..30}; do
    if docker compose -f "${COMPOSE_FILE}" ps opensips | grep -q "healthy"; then
        log_info "OpenSIPS healthy."
        break
    fi
    sleep 2
done

log_info "=== Wave 3: Backup ==="
docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" pull backup 2>&1 | tail -5
docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" up -d backup

log_info "=== Verificacao Final ==="
RUNNING=$(docker compose -f "${COMPOSE_FILE}" ps --format '{{.Name}}' | wc -l)
log_info "Containers em execucao: ${RUNNING}"

if curl -sf http://localhost:8084/login.php | grep -qi "opensips\|TSiSIP\|ocp"; then
    log_info "OCP responde em http://localhost:8084 — deploy bem-sucedido."
else
    log_warn "OCP nao responde corretamente em :8084."
fi

RAM_USED=$(free -m | awk '/^Mem:/ {print $3}')
RAM_TOTAL=$(free -m | awk '/^Mem:/ {print $2}')
log_info "Uso de RAM: ${RAM_USED}/${RAM_TOTAL} MB"

log_info "=== Deploy VPS-Lite concluido ==="
echo ""
docker compose -f "${COMPOSE_FILE}" ps --format 'table {{.Name}}\t{{.Status}}\t{{.Ports}}'
