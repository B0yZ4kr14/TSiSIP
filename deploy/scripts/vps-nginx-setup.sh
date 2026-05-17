#!/usr/bin/env bash
#
# Configura o Nginx existente da TSiAPP para rotear /TSiSIP/ para o OCP
# Executar na VPS como root ou com sudo.
#
set -euo pipefail

NGINX_SITE="/etc/nginx/sites-available/tsiapp-https"
NGINX_CONF="/etc/nginx/nginx.conf"
LOCATION_FILE="/opt/tsisip/deploy/nginx/tsisip-location.conf"

log_info() { echo "[INFO] $*"; }
log_warn() { echo "[WARN] $*"; }
log_fatal() { echo "[FATAL] $*" >&2; exit 1; }

log_info "=== TSiSIP Nginx Integration ==="

if [ ! -f "${NGINX_SITE}" ]; then
    log_fatal "Site Nginx nao encontrado: ${NGINX_SITE}"
fi

# Adicionar limit_req_zone e limit_conn_zone no nginx.conf se nao existirem
if ! grep -q "limit_req_zone.*tsisip_web" "${NGINX_CONF}"; then
    log_info "Adicionando rate limiting zones ao nginx.conf..."
    sed -i '/http {/a\    limit_req_zone $binary_remote_addr zone=tsisip_web:10m rate=30r/m;\n    limit_conn_zone $binary_remote_addr zone=tsisip_conn:10m;' "${NGINX_CONF}"
fi

# Verificar se o location /TSiSIP/ ja existe
if grep -q "location /TSiSIP/" "${NGINX_SITE}"; then
    log_warn "Location /TSiSIP/ ja existe em ${NGINX_SITE}. Pulando."
else
    log_info "Adicionando location /TSiSIP/ ao site..."
    # Inserir antes do ultimo closing brace do server block
    # Usar awk para inserir antes do ultimo '}'
    awk '
        /^}/ && !done {
            print "    location /TSiSIP/ {"
            print "        limit_req zone=tsisip_web burst=10 nodelay;"
            print "        limit_conn tsisip_conn 10;"
            print "        proxy_pass         http://127.0.0.1:8084/;"
            print "        proxy_http_version 1.1;"
            print "        proxy_set_header Host              $host;"
            print "        proxy_set_header X-Real-IP         $remote_addr;"
            print "        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;"
            print "        proxy_set_header X-Forwarded-Proto $scheme;"
            print "        proxy_connect_timeout 30s;"
            print "        proxy_send_timeout    30s;"
            print "        proxy_read_timeout    30s;"
            print "    }"
            print ""
            print "    location /TSiSIP/health {"
            print "        proxy_pass         http://127.0.0.1:8084/login.php;"
            print "        access_log off;"
            print "        allow 127.0.0.1;"
            print "        allow 10.0.0.0/8;"
            print "        allow 172.16.0.0/12;"
            print "        allow 192.168.0.0/16;"
            print "        allow 100.64.0.0/10;"
            print "        deny all;"
            print "    }"
            done = 1
        }
        { print }
    ' "${NGINX_SITE}" > "${NGINX_SITE}.tmp" && mv "${NGINX_SITE}.tmp" "${NGINX_SITE}"
fi

nginx -t && systemctl reload nginx
log_info "Nginx configurado e recarregado com sucesso."
