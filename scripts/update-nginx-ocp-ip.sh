#!/bin/bash
# scripts/update-nginx-ocp-ip.sh
# Auto-update Nginx upstream IP for OCP container to avoid Docker IP drift.
# Run via cron (e.g., * * * * * /opt/tsisip/scripts/update-nginx-ocp-ip.sh)
# or as a post-deploy hook.

set -euo pipefail

NGINX_CONF="${NGINX_CONF:-/etc/nginx/sites-enabled/tsiapp-https}"
NGINX_MAIN="${NGINX_MAIN:-/etc/nginx/nginx.conf}"
CONTAINER_NAME="${CONTAINER_NAME:-tsisip-ocp-1}"
NETWORK_NAME="${NETWORK_NAME:-tsisip_sip_internal}"
OPENSIPS_CONTAINER="${OPENSIPS_CONTAINER:-tsisip-opensips-1}"
OPENSIPS_NETWORK="${OPENSIPS_NETWORK:-tsisip_sip_edge}"

# Resolve current OCP IP on the Docker internal network
CURRENT_IP=$(docker inspect "${CONTAINER_NAME}" \
    --format "{{range \$k, \$v := .NetworkSettings.Networks}}{{if eq \$k \"${NETWORK_NAME}\"}}{{\$v.IPAddress}}{{end}}{{end}}" 2>/dev/null || true)

if [ -z "${CURRENT_IP}" ]; then
    echo "ERROR: Could not resolve IP for ${CONTAINER_NAME} on ${NETWORK_NAME}" >&2
    exit 1
fi

# Extract currently configured IPs from Nginx conf
CONFIGURED_OCP_IP=$(grep -oP 'proxy_pass\s+http://\K172\.19\.0\.[0-9]+' "${NGINX_CONF}" | head -1 || true)
CONFIGURED_WS_IP=$(grep -oP 'proxy_pass\s+http://\K172\.18\.0\.[0-9]+' "${NGINX_CONF}" | head -1 || true)

# Resolve OpenSIPS IP for WebSocket proxy
OPENSIPS_IP=$(docker inspect "${OPENSIPS_CONTAINER}" \
    --format "{{range \$k, \$v := .NetworkSettings.Networks}}{{if eq \$k \"${OPENSIPS_NETWORK}\"}}{{\$v.IPAddress}}{{end}}{{end}}" 2>/dev/null || true)

if [ -z "${OPENSIPS_IP}" ]; then
    echo "WARNING: Could not resolve IP for ${OPENSIPS_CONTAINER} on ${OPENSIPS_NETWORK}" >&2
    # Continue anyway — OCP IP update is the critical path
fi

if [ "${CONFIGURED_OCP_IP}" = "${CURRENT_IP}" ]; then
    if [ -n "${OPENSIPS_IP}" ] && [ "${CONFIGURED_WS_IP}" = "${OPENSIPS_IP}" ]; then
        # No change needed
        exit 0
    fi
fi

# Update Nginx configuration (both sites-enabled and main nginx.conf)
sed -i "s|proxy_pass http://172\.19\.0\.[0-9]\+:80/;|proxy_pass http://${CURRENT_IP}:80/;|g" "${NGINX_CONF}"
sed -i "s|server 172\.18\.0\.[0-9]\+:80;|server ${CURRENT_IP}:80;|g" "${NGINX_MAIN}"
sed -i "s|server 172\.19\.0\.[0-9]\+:80;|server ${CURRENT_IP}:80;|g" "${NGINX_MAIN}"

# Update WebSocket proxy IP for OpenSIPS (if resolved)
if [ -n "${OPENSIPS_IP}" ]; then
    sed -i "s|proxy_pass http://172\.18\.0\.[0-9]\+:8080;|proxy_pass http://${OPENSIPS_IP}:8080;|g" "${NGINX_CONF}"
fi

# Validate and reload
nginx -t
systemctl reload nginx

if [ -n "${OPENSIPS_IP}" ] && [ "${CONFIGURED_WS_IP}" != "${OPENSIPS_IP}" ]; then
    echo "Updated Nginx WebSocket upstream: ${CONFIGURED_WS_IP} -> ${OPENSIPS_IP}"
fi
echo "Updated Nginx OCP upstream: ${CONFIGURED_OCP_IP} -> ${CURRENT_IP}"
