# TSiSIP OpenSIPS Control Panel v9 — Docker Image
# Base: PHP 8.2 + Apache on Debian Bookworm
# Delivers OCP v9 with TSiSIP rebranding layer

FROM php:8.2-apache-bookworm@sha256:5e0eabeab4ac6d58b3fb9af50bd7c57c89aacb8ed7090e31b28c1e9056dd94fb

LABEL org.opencontainers.image.title="TSiSIP OCP v9"
LABEL org.opencontainers.image.description="OpenSIPS Control Panel v9 with TSiSIP premium branding"
LABEL org.tsisip.feature="002-tsisip-ocp-rebrand"

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    gettext \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by OCP v9
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    gettext

# Enable Apache mod_rewrite for OCP pretty URLs
RUN a2enmod rewrite

# Copy OCP application source (stubs; replace with actual OCP v9 source in production)
COPY web/ /var/www/html/

# Copy wiki documentation for the embedded wiki renderer
COPY docs/wiki/ /var/www/docs/wiki/

# Set correct ownership for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Health check: expect 200 OK with TSiSIP logo in response body
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsSL http://localhost/login.php | grep -q 'TSiSIP' || exit 1

EXPOSE 80

CMD ["apache2-foreground"]
