FROM pgbouncer/pgbouncer@sha256:aa8a38b7b33e5fe70c679053f97a8e55c74d52b00c195f0880845e52b50ce516

# Install envsubst for template rendering
USER root
RUN apk add --no-cache gettext postgresql-client

COPY pgbouncer.ini.tpl /etc/pgbouncer/pgbouncer.ini.tpl
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Ensure pgbouncer binary is in PATH
RUN ln -sf /opt/pgbouncer/pgbouncer /usr/local/bin/pgbouncer

# PgBouncer runs as non-root by default in the official image
USER postgres

EXPOSE 5432

ENTRYPOINT ["/entrypoint.sh"]
