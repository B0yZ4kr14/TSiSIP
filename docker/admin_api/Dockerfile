# TSiSIP Admin API — Subscriber Proxy Microservice
# Minimal PHP 8.2 Apache image for internal subscriber mutations

# Base image pinned to SHA256 digest for supply-chain determinism.
# Fetched: 2026-05-24
# Verify: docker inspect php:8.2-apache --format='{{index .RepoDigests 0}}'
FROM php:8.2-apache-bookworm@sha256:5e0eabeab4ac6d58b3fb9af50bd7c57c89aacb8ed7090e31b28c1e9056dd94fb

# Install PDO PostgreSQL extension
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite for routing
RUN a2enmod rewrite

# Copy application
COPY docker/admin_api/src/ /var/www/html/

# Copy entrypoint for secret permission fix
COPY docker/admin_api/entrypoint.sh /usr/local/bin/admin-api-entrypoint.sh
RUN chmod +x /usr/local/bin/admin-api-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/admin-api-entrypoint.sh"]

# Internal port only — not published in compose
EXPOSE 8080

# Run Apache on port 8080 (internal only)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:8080/' /etc/apache2/sites-available/000-default.conf

# Health check — lightweight HTTP probe on internal port
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -sS http://127.0.0.1:8080/health.php > /dev/null || exit 1

# Security: run as non-root (www-data is default in php-apache)
# Drop all capabilities except necessary ones at runtime via compose
