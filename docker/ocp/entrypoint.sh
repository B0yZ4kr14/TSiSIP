#!/bin/sh
set -euo pipefail

# Fix secret permissions so www-data can read them
for secret in db_password auth_secret topology_secret trunk_cred_key; do
    src="/run/secrets/${secret}"
    dst="/tmp/${secret}"
    if [ -f "$src" ]; then
        cp "$src" "$dst"
        chmod 644 "$dst"
    fi
done

# Export audit retention setting for PHP runtime
export OCP_AUDIT_RETENTION_DAYS="${OCP_AUDIT_RETENTION_DAYS:-90}"

# Ensure log directory exists
mkdir -p /var/log/tsisip
chown www-data:www-data /var/log/tsisip 2>/dev/null || true

# Start cron daemon for scheduled audit retention
if [ -x /usr/sbin/cron ]; then
    /usr/sbin/cron
fi

# Update Apache to serve from correct directory
export APACHE_DOCUMENT_ROOT=${APACHE_DOCUMENT_ROOT:-/var/www/html}

exec apache2-foreground
