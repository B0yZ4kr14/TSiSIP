FROM debian:bookworm-slim@sha256:67b30a61dc87758f0caf819646104f29ecbda97d920aaf5edc834128ac8493d3

# Install rtpengine and netcat
RUN apt-get update && apt-get upgrade -y --no-install-recommends \
    && apt-get install -y --no-install-recommends \
    rtpengine=10.5.3.5-1 \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Copy health check script
COPY healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Health check with staggered start period
HEALTHCHECK --interval=15s --timeout=5s --start-period=25s --start-interval=5s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh || exit 1

# 22222/udp is the ng-control port — internal Docker networks only, never host-published
EXPOSE 10000-20000/udp

ENTRYPOINT ["rtpengine"]
CMD ["--foreground", "--log-stderr"]
