#!/bin/bash
# TSiSIP Orquestrador Completo: Build → Push → Deploy → Nginx
# FASES: 1=Build 2=Push 3=Ansible 4=Hardening 5=Deploy 6=Nginx 7=Health
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
ENV_FILE="$HOME/.env"
VENV_DIR="$PROJECT_ROOT/.ansible-venv"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; }
step()  { echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; echo -e "${BLUE}  $*${NC}"; echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; }

# ─── Extrair segredos de ~/.env ───
info "Extraindo segredos de $ENV_FILE..."
GITHUB_TOKEN=""
TSiAPP_HOST=""
TSiAPP_USER=""
if [ -f "$ENV_FILE" ]; then
    while IFS='=' read -r key value; do
        case "$key" in
            GITHUB_TOKEN) GITHUB_TOKEN="${value//\"/\"}" ;;
            TSiAPP_HOST)  TSiAPP_HOST="${value//\"/\"}" ;;
            TSiAPP_USER)  TSiAPP_USER="${value//\"/\"}" ;;
        esac
    done < <(grep -E '^(GITHUB_TOKEN|TSiAPP_HOST|TSiAPP_USER)=' "$ENV_FILE" 2>/dev/null || true)
fi

if [ -z "$GITHUB_TOKEN" ]; then error "GITHUB_TOKEN não encontrado em ~/.env"; exit 1; fi
if [ -z "$TSiAPP_HOST" ];  then TSiAPP_HOST="100.111.74.69"; fi
if [ -z "$TSiAPP_USER" ];  then TSiAPP_USER="tsi"; fi

info "Target: $TSiAPP_USER@$TSiAPP_HOST"
info "GitHub token: ${GITHUB_TOKEN:0:8}... (redacted)"

# ═══════════════════════════════════════════════════════════════════════
# FASE 1: BUILD / RETAG DE TODAS AS IMAGENS
# ═══════════════════════════════════════════════════════════════════════
step "FASE 1/7: Build e Retag de Imagens Docker"

cd "$PROJECT_ROOT"

# Retag das imagens que estão como :test → :latest
for img in opensips prometheus grafana opensips-exporter; do
    if docker images --format '{{.Repository}}:{{.Tag}}' | grep -q "tsisip/${img}:test"; then
        info "Retag: tsisip/${img}:test → tsisip/${img}:latest"
        docker tag "tsisip/${img}:test" "tsisip/${img}:latest"
    fi
done

# Build das imagens que ainda não existem como :latest
info "Build de imagens faltantes via docker compose..."
docker compose build --parallel backup postgres asterisk anomaly-detector 2>/dev/null || true

# Força retag de todas para garantir consistência
for img in backup postgres asterisk anomaly-detector; do
    if docker images --format '{{.Repository}}:{{.Tag}}' | grep -q "tsisip/${img}:"; then
        EXISTING_TAG=$(docker images --format '{{.Repository}}:{{.Tag}}' | grep "tsisip/${img}:" | head -1 | cut -d: -f2)
        if [ "$EXISTING_TAG" != "latest" ]; then
            info "Retag: tsisip/${img}:${EXISTING_TAG} → tsisip/${img}:latest"
            docker tag "tsisip/${img}:${EXISTING_TAG}" "tsisip/${img}:latest"
        fi
    fi
done

info "Imagens locais disponíveis:"
docker images --format 'table {{.Repository}}\t{{.Tag}}\t{{.Size}}' | grep '^tsisip/' | sort

# ═══════════════════════════════════════════════════════════════════════
# FASE 2: PUSH PARA GHCR
# ═══════════════════════════════════════════════════════════════════════
step "FASE 2/7: Push para GitHub Container Registry (GHCR)"

echo "$GITHUB_TOKEN" | docker login ghcr.io -u B0yZ4kr14 --password-stdin >/dev/null 2>&1
info "Login GHCR: OK"

REGISTRY_PREFIX="ghcr.io/b0yz4kr14"
IMAGES_TO_PUSH=(
    "tsisip/opensips:latest"
    "tsisip/rtpengine:latest"
    "tsisip/ocp:latest"
    "tsisip/postgres:latest"
    "tsisip/asterisk:latest"
    "tsisip/prometheus:latest"
    "tsisip/grafana:latest"
    "tsisip/opensips-exporter:latest"
    "tsisip/anomaly-detector:latest"
    "tsisip/backup:latest"
)

for img in "${IMAGES_TO_PUSH[@]}"; do
    REPO=$(echo "$img" | cut -d: -f1)
    TAG=$(echo "$img" | cut -d: -f2)
    GHCR_IMG="${REGISTRY_PREFIX}/${REPO}:${TAG}"
    
    if docker images --format '{{.Repository}}:{{.Tag}}' | grep -q "^${img}$"; then
        info "Tagging ${img} → ${GHCR_IMG}"
        docker tag "${img}" "${GHCR_IMG}"
        info "Pushing ${GHCR_IMG}..."
        docker push "${GHCR_IMG}" || warn "Push falhou para ${GHCR_IMG}"
    else
        warn "Imagem local não encontrada: ${img}"
    fi
done

# ═══════════════════════════════════════════════════════════════════════
# FASE 3: INSTALAÇÃO DO ANSIBLE
# ═══════════════════════════════════════════════════════════════════════
step "FASE 3/7: Instalação do Ansible"

if command -v ansible-playbook >/dev/null 2>&1; then
    info "Ansible já instalado: $(ansible-playbook --version | head -1)"
else
    info "Criando venv Python para Ansible..."
    python3 -m venv "$VENV_DIR"
    source "$VENV_DIR/bin/activate"
    pip install --quiet ansible
    info "Ansible instalado: $(ansible-playbook --version | head -1)"
fi

# Garante que o PATH inclui o venv
export PATH="$VENV_DIR/bin:$PATH"

# ═══════════════════════════════════════════════════════════════════════
# FASE 4: HARDENING DA VPS
# ═══════════════════════════════════════════════════════════════════════
step "FASE 4/7: Server Hardening (Ansible)"

cd "$PROJECT_ROOT/deploy/ansible"

export ANSIBLE_HOST_KEY_CHECKING=False
export ANSIBLE_PYTHON_INTERPRETER=/usr/bin/python3

info "Executando playbook-hardening.yml..."
ansible-playbook \
    -i inventory.yml \
    -e "ansible_host=$TSiAPP_HOST" \
    -e "ansible_user=$TSiAPP_USER" \
    -e "ansible_ssh_private_key_file=$HOME/.ssh/id_ed25519_b0yz4kr14" \
    playbook-hardening.yml || warn "Hardening retornou erro (pode ser OK se já estava aplicado)"

# ═══════════════════════════════════════════════════════════════════════
# FASE 5: DEPLOY DO STACK
# ═══════════════════════════════════════════════════════════════════════
step "FASE 5/7: Deploy do Stack TSiSIP (Ansible)"

info "Atualizando docker-compose.prod.yml para usar GHCR..."
sed -i "s|image: tsisip/|image: ${REGISTRY_PREFIX}/tsisip/|g" "$PROJECT_ROOT/docker-compose.prod.yml"

info "Executando playbook-deploy.yml..."
ansible-playbook \
    -i inventory.yml \
    -e "ansible_host=$TSiAPP_HOST" \
    -e "ansible_user=$TSiAPP_USER" \
    -e "ansible_ssh_private_key_file=$HOME/.ssh/id_ed25519_b0yz4kr14" \
    playbook-deploy.yml || { error "Deploy falhou"; exit 1; }

# ═══════════════════════════════════════════════════════════════════════
# FASE 6: CONFIGURAÇÃO DO NGINX
# ═══════════════════════════════════════════════════════════════════════
step "FASE 6/7: Configuração do Nginx (location /TSiSIP/)"

info "Copiando configuração Nginx para VPS..."
scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
    -i "$HOME/.ssh/id_ed25519_b0yz4kr14" \
    "$PROJECT_ROOT/deploy/nginx/tsisip-location.conf" \
    "${TSiAPP_USER}@${TSiAPP_HOST}:/tmp/tsisip-location.conf"

ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
    -i "$HOME/.ssh/id_ed25519_b0yz4kr14" \
    "${TSiAPP_USER}@${TSiAPP_HOST}" << SSH_EOF
    set -e
    echo "=== Verificando Nginx ==="
    sudo nginx -v
    
    echo "=== Injetando location /TSiSIP/ no site tsiapp-https ==="
    # Remove bloco antigo se existir
    sudo sed -i '/# BEGIN TSiSIP/,/# END TSiSIP/d' /etc/nginx/sites-available/tsiapp-https
    
    # Adiciona novo bloco antes do último '}' do server block
    sudo bash -c '
        BLOCK="\$(cat /tmp/tsisip-location.conf)"
        # Encontra a última linha do server block e insere antes
        awk "\"/server {/{s=1} s{buf=buf\\\$0\"\\n\"; if(\\\$0~/^}/){exit}} END{print buf}" /etc/nginx/sites-available/tsiapp-https > /tmp/nginx-server-block.txt
    '
    
    # Método mais simples: append ao arquivo com marcadores
    echo "# BEGIN TSiSIP" | sudo tee -a /etc/nginx/sites-available/tsiapp-https >/dev/null
    cat /tmp/tsisip-location.conf | sudo tee -a /etc/nginx/sites-available/tsiapp-https >/dev/null
    echo "# END TSiSIP" | sudo tee -a /etc/nginx/sites-available/tsiapp-https >/dev/null
    
    echo "=== Adicionando limit zones no nginx.conf ==="
    if ! grep -q "limit_req_zone.*tsisip_web" /etc/nginx/nginx.conf; then
        sudo sed -i '/http {/a\    limit_req_zone \$binary_remote_addr zone=tsisip_web:10m rate=30r/m;\n    limit_conn_zone \$binary_remote_addr zone=tsisip_conn:10m;' /etc/nginx/nginx.conf
    fi
    
    echo "=== Testando configuração ==="
    sudo nginx -t
    
    echo "=== Recarregando Nginx ==="
    sudo systemctl reload nginx
    
    echo "Nginx configurado com sucesso!"
SSH_EOF

# ═══════════════════════════════════════════════════════════════════════
# FASE 7: HEALTH CHECKS FINAIS
# ═══════════════════════════════════════════════════════════════════════
step "FASE 7/7: Health Checks Finais"

ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
    -i "$HOME/.ssh/id_ed25519_b0yz4kr14" \
    "${TSiAPP_USER}@${TSiAPP_HOST}" << SSH_EOF
    set -e
    echo "=== Docker Containers ==="
    docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "tsisip|opensips|rtpengine|postgres" || echo "Containers TSiSIP não encontrados (pode estar em outro projeto)"
    
    echo ""
    echo "=== OCP Health (localhost:8084) ==="
    curl -s -o /dev/null -w "%{http_code}" http://localhost:8084/login.php || echo "FALHOU"
    
    echo ""
    echo "=== OpenSIPS Ports ==="
    ss -ulnp | grep 5060 || echo "OpenSIPS UDP não escutando"
    ss -tlnp | grep 5060 || echo "OpenSIPS TCP não escutando"
    ss -tlnp | grep 5061 || echo "OpenSIPS TLS não escutando"
    
    echo ""
    echo "=== Nginx Location /TSiSIP/ ==="
    curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost/TSiSIP/health || echo "Nginx health FALHOU"
SSH_EOF

step "ORQUESTRAÇÃO COMPLETA!"
info "Verifique os logs acima para confirmar todos os serviços."
