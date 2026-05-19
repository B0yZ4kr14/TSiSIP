FROM postgres:16@sha256:b6ccf02e9b47eac0d67b5eaa0ef56fd59163bffa5506f64e96ceb5053130ec86

# Install netcat for health checks
RUN apt-get update && apt-get install -y --no-install-recommends netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Copy health check script
COPY docker/healthcheck/postgres-health.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Health check with staggered start period
HEALTHCHECK --interval=10s --timeout=5s --start-period=20s --start-interval=5s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh || exit 1
