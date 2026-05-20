#!/bin/sh
set -eu

# Fix secret permissions so www-data can read them
for secret in db_password auth_secret topology_secret; do
    src="/run/secrets/${secret}"
    dst="/tmp/${secret}"
    if [ -f "$src" ]; then
        cp "$src" "$dst"
        chmod 644 "$dst"
    fi
done

# Update Apache to serve from correct directory
export APACHE_DOCUMENT_ROOT=${APACHE_DOCUMENT_ROOT:-/var/www/html}

exec apache2-foreground
