FROM debian:bookworm-slim

# Install rtpengine and netcat
RUN apt-get update && apt-get install -y --no-install-recommends \
    rtpengine \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Copy health check script
COPY docker/healthcheck/rtpengine-health.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Health check with staggered start period
HEALTHCHECK --interval=15s --timeout=5s --start-period=25s --start-interval=5s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh || exit 1

EXPOSE 22222/udp 10000-20000/udp

CMD ["rtpengine", "--foreground", "--log-stderr"]
