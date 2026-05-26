#!/bin/sh
set -eu

# Fix secret permissions so www-data can read them
for secret in db_password proxy_api_secret; do
    src="/run/secrets/${secret}"
    dst="/tmp/${secret}"
    if [ -f "$src" ]; then
        cp "$src" "$dst"
        chmod 644 "$dst"
    fi
done

# Start Apache
exec apache2-foreground
