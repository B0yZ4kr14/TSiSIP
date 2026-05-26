#!/bin/bash
# scripts/update-nginx-ocp-ip.sh
# Auto-update Nginx upstream IP for OCP container to avoid Docker IP drift.
# Run via cron (e.g., * * * * * /opt/tsisip/scripts/update-nginx-ocp-ip.sh)
# or as a post-deploy hook.

set -euo pipefail

NGINX_CONF="${NGINX_CONF:-/etc/nginx/sites-enabled/tsiapp-https}"
CONTAINER_NAME="${CONTAINER_NAME:-tsisip-ocp-1}"
NETWORK_NAME="${NETWORK_NAME:-tsisip_sip_internal}"

# Resolve current OCP IP on the Docker internal network
CURRENT_IP=$(docker inspect "${CONTAINER_NAME}" \
    --format "{{range \$k, \$v := .NetworkSettings.Networks}}{{if eq \$k \"${NETWORK_NAME}\"}}{{\$v.IPAddress}}{{end}}{{end}}" 2>/dev/null || true)

if [ -z "${CURRENT_IP}" ]; then
    echo "ERROR: Could not resolve IP for ${CONTAINER_NAME} on ${NETWORK_NAME}" >&2
    exit 1
fi

# Extract currently configured IP from Nginx conf
CONFIGURED_IP=$(grep -oP 'proxy_pass\s+http://\K172\.19\.0\.[0-9]+' "${NGINX_CONF}" | head -1 || true)

if [ "${CONFIGURED_IP}" = "${CURRENT_IP}" ]; then
    # No change needed
    exit 0
fi

# Update Nginx configuration
sed -i "s|proxy_pass http://172\.19\.0\.[0-9]\+:80/;|proxy_pass http://${CURRENT_IP}:80/;|g" "${NGINX_CONF}"

# Validate and reload
nginx -t
systemctl reload nginx

echo "Updated Nginx OCP upstream: ${CONFIGURED_IP} -> ${CURRENT_IP}"
