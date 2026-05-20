FROM postgres:16@sha256:b6ccf02e9b47eac0d67b5eaa0ef56fd59163bffa5506f64e96ceb5053130ec86

# Install dependencies: rclone, openssl, cron, gettext
RUN apt-get update && apt-get install -y --no-install-recommends \
    rclone \
    openssl \
    cron \
    gettext \
    ca-certificates \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Create non-root user for future migration (container still runs as root for cron daemon)
RUN groupadd -r tsisip-backup && useradd -r -g tsisip-backup -G postgres tsisip-backup

# Create backup directories with correct ownership
RUN mkdir -p /backup/daily /backup/wal /backup/validate /backup/metrics /tmp/backup \
    && chown -R tsisip-backup:postgres /backup /tmp/backup

# Copy scripts
COPY *.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/*.sh

# Prepare rclone config directory for non-root user
RUN mkdir -p /etc/rclone && chown -R tsisip-backup:tsisip-backup /etc/rclone
COPY --chown=tsisip-backup:tsisip-backup rclone.conf.tpl /etc/rclone/rclone.conf.tpl

# Environment defaults
ENV BACKUP_SCHEDULE="0 2 * * *" \
    PURGE_SCHEDULE="0 3 * * *" \
    VALIDATE_SCHEDULE="0 4 * * *" \
    REPLICATE_SCHEDULE="0 * * * *" \
    RPO_SCHEDULE="*/5 * * * *" \
    QUOTA_SCHEDULE="*/10 * * * *" \
    BACKUP_RETENTION_DAYS=30 \
    WAL_RETENTION_DAYS=37 \
    BACKUP_QUOTA_GB=100 \
    PGHOST=postgres \
    PGPORT=5432 \
    PGDATABASE=opensips \
    PGUSER=opensips \
    ENCRYPTION_KEY_FILE=/run/secrets/backup_encryption_key \
    BACKUP_DIR=/backup/daily \
    WAL_DIR=/backup/wal \
    METRICS_DIR=/backup/metrics \
    METRICS_PORT=9101 \
    METRICS_ADDR=0.0.0.0

# Health check
HEALTHCHECK --interval=60s --timeout=10s --start-period=30s --retries=3 \
    CMD pg_isready -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["cron", "-f"]
