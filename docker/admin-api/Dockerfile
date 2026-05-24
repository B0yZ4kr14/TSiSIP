# TSiSIP Admin API — Subscriber Proxy Microservice
# Minimal PHP 8.2 Apache image for internal subscriber mutations

FROM php:8.2-apache

# Install PDO PostgreSQL extension
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite for routing
RUN a2enmod rewrite

# Copy application
COPY src/ /var/www/html/

# Internal port only — not published in compose
EXPOSE 8080

# Run Apache on port 8080 (internal only)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:8080/' /etc/apache2/sites-available/000-default.conf

# Security: run as non-root (www-data is default in php-apache)
# Drop all capabilities except necessary ones at runtime via compose
