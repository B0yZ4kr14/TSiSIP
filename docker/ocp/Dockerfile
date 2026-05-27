# TSiSIP OpenSIPS Control Panel v9 — Docker Image
# Base: PHP 8.2 + Apache on Debian Bookworm
# Delivers OCP v9 with TSiSIP rebranding layer

FROM php:8.2-apache-bookworm@sha256:5e0eabeab4ac6d58b3fb9af50bd7c57c89aacb8ed7090e31b28c1e9056dd94fb

LABEL org.opencontainers.image.title="TSiSIP OCP v9"
LABEL org.opencontainers.image.description="OpenSIPS Control Panel v9 with TSiSIP premium branding"
LABEL org.tsisip.feature="002-tsisip-ocp-rebrand"

# Install system dependencies
RUN apt-get update && apt-get upgrade -y --no-install-recommends \
    && apt-get install -y --no-install-recommends \
    cron \
    gettext \
    libpq-dev \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by OCP v9
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    gettext

# Enable Apache mod_rewrite for OCP pretty URLs
RUN a2enmod rewrite

# Copy PHP session security hardening configuration
# Aligns with CANONICAL-SPEC Section 17 Security Model
COPY docker/ocp/php-session-security.ini /usr/local/etc/php/conf.d/99-session-security.ini
COPY docker/ocp/php.ini /usr/local/etc/php/conf.d/98-memory-opcache.ini

# Copy OCP application source (stubs; replace with actual OCP v9 source in production)
COPY web/ /var/www/html/

# Copy wiki documentation for the embedded wiki renderer
COPY ./docs/wiki /var/www/docs/wiki

# Set correct ownership for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create log directory for audit retention and cron output
RUN mkdir -p /var/log/tsisip \
    && chown www-data:www-data /var/log/tsisip

# Copy audit retention cron script
COPY docker/ocp/cron/audit-retention.sh /usr/local/bin/audit-retention.sh
RUN chmod +x /usr/local/bin/audit-retention.sh

# Install daily cron job at 03:17 for audit retention
RUN echo "17 3 * * * root /usr/local/bin/audit-retention.sh >> /var/log/tsisip/audit-retention-cron.log 2>&1" > /etc/cron.d/tsisip-audit-retention \
    && chmod 0644 /etc/cron.d/tsisip-audit-retention

# Copy custom entrypoint to fix secret permissions for www-data
COPY docker/ocp/entrypoint.sh /usr/local/bin/ocp-entrypoint.sh
RUN chmod +x /usr/local/bin/ocp-entrypoint.sh

# Health check: expect 200 OK with TSiSIP logo in response body
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsSL http://localhost/login.php | grep -q 'TSiSIP' || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/ocp-entrypoint.sh"]
