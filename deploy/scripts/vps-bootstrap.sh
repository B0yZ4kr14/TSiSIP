#!/usr/bin/env bash
#
# TSiSIP VPS Bootstrap — Script de inicializacao automatica
# Executar uma unica vez na VPS TSiAPP apos reinstall ou primeira configuracao.
# Este script prepara o ambiente e executa o deploy do perfil vps-lite.
#
set -euo pipefail

TSISIP_DIR="/opt/tsisip"
REPO_URL="https://github.com/B0yZ4kr14/TSiSIP.git"
GITHUB_USER="b0yz4kr14"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info()  { echo -e "${GREEN}[BOOTSTRAP]${NC} $*"; }
log_warn()  { echo -e "${YELLOW}[BOOTSTRAP]${NC} $*"; }
log_fatal() { echo -e "${RED}[BOOTSTRAP]${NC} $*" >&2; exit 1; }

log_info "=== TSiSIP VPS Bootstrap ==="

# === 1. Sistema Base ===
log_info "Atualizando pacotes..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq curl git ca-certificates openssl jq ufw fail2ban

# === 2. Docker ===
if ! command -v docker &>/dev/null; then
    log_info "Instalando Docker..."
    curl -fsSL https://get.docker.com | sh
    usermod -aG docker tsi 2>/dev/null || true
    systemctl enable docker
    systemctl start docker
else
    log_info "Docker ja instalado: $(docker version --format '{{.Server.Version}}')"
fi

# Docker Compose plugin
if ! docker compose version &>/dev/null; then
    log_info "Instalando Docker Compose plugin..."
    apt-get install -y -qq docker-compose-plugin
fi

# === 3. UFW + fail2ban (basico) ===
log_info "Configurando firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 5060/udp
ufw allow 5060/tcp
ufw allow 5061/tcp
ufw allow 10000:20000/udp
ufw --force enable

systemctl enable fail2ban
systemctl start fail2ban

# === 4. Diretorio do Projeto ===
if [[ -d "${TSISIP_DIR}/.git" ]]; then
    log_info "Repo existente encontrado. Atualizando..."
    cd "${TSISIP_DIR}"
    git pull --ff-only
else
    log_info "Clonando repositorio..."
    mkdir -p "${TSISIP_DIR}"
    git clone "${REPO_URL}" "${TSISIP_DIR}"
    cd "${TSISIP_DIR}"
fi

# === 5. Secrets ===
log_info "Verificando secrets..."
mkdir -p "${TSISIP_DIR}/secrets"

generate_secret() {
    local file="$1"
    local size="${2:-32}"
    if [[ ! -f "${file}" ]] || [[ $(wc -c < "${file}" | tr -d ' ') -ne ${size} ]]; then
        openssl rand -base64 48 | head -c ${size} > "${file}"
        chmod 600 "${file}"
        log_info "Gerado: ${file} (${size} bytes)"
    fi
}

generate_secret "${TSISIP_DIR}/secrets/db_password" 16
generate_secret "${TSISIP_DIR}/secrets/auth_secret" 32
generate_secret "${TSISIP_DIR}/secrets/topology_secret" 32
generate_secret "${TSISIP_DIR}/secrets/backup_encryption_key" 64

# Certificados TLS dummy (para OpenSIPS nao falhar no startup)
# Em producao, substituir por certificados reais
if [[ ! -f "${TSISIP_DIR}/secrets/server.crt" ]]; then
    log_warn "Gerando certificados TLS dummy (substituir em producao!)"
    mkdir -p "${TSISIP_DIR}/secrets"
    openssl req -x509 -newkey rsa:2048 -keyout "${TSISIP_DIR}/secrets/server.key" \
        -out "${TSISIP_DIR}/secrets/server.crt" -days 365 -nodes \
        -subj "/CN=tsiapp.io/O=TSiSIP/C=BR" 2>/dev/null
    cp "${TSISIP_DIR}/secrets/server.crt" "${TSISIP_DIR}/secrets/ca.crt"
    touch "${TSISIP_DIR}/secrets/crl.pem"
    chmod 600 "${TSISIP_DIR}/secrets/server.key"
    chmod 644 "${TSISIP_DIR}/secrets/server.crt" "${TSISIP_DIR}/secrets/ca.crt"
fi

# === 6. .env ===
log_info "Configurando .env..."
cat > "${TSISIP_DIR}/.env" <<ENVEOF
OPENSIPS_LISTEN_IP=0.0.0.0
HOST_PUBLIC_IP=179.190.15.116
RTPENGINE_PRIVATE_IP=172.19.0.1
RTPENGINE_INTERNAL_IP=172.21.0.1
ENVEOF

# === 7. GHCR Login ===
log_info "Login no GHCR..."
if [[ -n "${GITHUB_TOKEN:-}" ]]; then
    echo "${GITHUB_TOKEN}" | docker login ghcr.io -u "${GITHUB_USER}" --password-stdin >/dev/null 2>&1
    log_info "Login GHCR OK."
else
    log_warn "GITHUB_TOKEN nao definido. Pulando login (pode falhar no pull)."
fi

# === 8. Deploy ===
log_info "Executando deploy vps-lite..."
cd "${TSISIP_DIR}"
./deploy/scripts/vps-deploy.sh

# === 9. Nginx ===
if [[ -f "/etc/nginx/nginx.conf" ]]; then
    log_info "Configurando Nginx..."
    ./deploy/scripts/vps-nginx-setup.sh || log_warn "Nginx setup falhou (ignorado)"
else
    log_warn "Nginx nao encontrado. Configuracao manual necessaria."
fi

# === 10. Systemd Service para TSiSIP ===
log_info "Criando systemd service tsisip-lite..."
cat > /etc/systemd/system/tsisip-lite.service <<'SERVICEEOF'
[Unit]
Description=TSiSIP VPS-Lite Stack
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/opt/tsisip
ExecStart=/usr/bin/docker compose -f /opt/tsisip/docker-compose.vps.yml up -d
ExecStop=/usr/bin/docker compose -f /opt/tsisip/docker-compose.vps.yml down
TimeoutStartSec=300

[Install]
WantedBy=multi-user.target
SERVICEEOF

systemctl daemon-reload
systemctl enable tsisip-lite

log_info "=== Bootstrap concluido ==="
log_info "Comandos uteis:"
echo "  systemctl status tsisip-lite"
echo "  docker compose -f ${TSISIP_DIR}/docker-compose.vps.yml ps"
echo "  docker compose -f ${TSISIP_DIR}/docker-compose.vps.yml logs -f opensips"
echo ""
log_info "OCP disponivel em: http://localhost:8084"
log_info "Proxy reverso Nginx: https://tsiapp.io/TSiSIP/"
