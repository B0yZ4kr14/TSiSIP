FROM debian:bookworm-slim

# Install Asterisk and netcat
RUN apt-get update && apt-get install -y --no-install-recommends \
    asterisk \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Copy health check script
COPY docker/healthcheck/asterisk-health.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Health check with staggered start period
HEALTHCHECK --interval=15s --timeout=5s --start-period=25s --start-interval=5s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh || exit 1

EXPOSE 5060/udp 5060/tcp 5038/tcp

CMD ["asterisk", "-f", "-vvv"]
