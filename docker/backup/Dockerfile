FROM postgres:16

# Install dependencies: rclone, openssl, cron, gettext
RUN apt-get update && apt-get install -y --no-install-recommends \
    rclone \
    openssl \
    cron \
    gettext \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Create backup directories
RUN mkdir -p /backup/daily /backup/wal /backup/validate /tmp/backup

# Copy scripts
COPY backup.sh /usr/local/bin/backup.sh
COPY wal-archive.sh /usr/local/bin/wal-archive.sh
COPY encrypt.sh /usr/local/bin/encrypt.sh
COPY purge.sh /usr/local/bin/purge.sh
COPY validate.sh /usr/local/bin/validate.sh
COPY replicate.sh /usr/local/bin/replicate.sh
COPY rclone.conf.tpl /etc/rclone/rclone.conf.tpl
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/*.sh

# Environment defaults
ENV BACKUP_SCHEDULE="0 2 * * *" \
    PURGE_SCHEDULE="0 3 * * *" \
    VALIDATE_SCHEDULE="0 4 * * *" \
    REPLICATE_SCHEDULE="0 * * * *" \
    BACKUP_RETENTION_DAYS=30 \
    WAL_RETENTION_DAYS=37 \
    PGHOST=postgres \
    PGPORT=5432 \
    PGDATABASE=opensips \
    PGUSER=opensips \
    ENCRYPTION_KEY_FILE=/run/secrets/backup_encryption_key \
    BACKUP_DIR=/backup/daily \
    WAL_DIR=/backup/wal

# Health check
HEALTHCHECK --interval=60s --timeout=10s --start-period=30s --retries=3 \
    CMD pg_isready -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["cron", "-f"]
