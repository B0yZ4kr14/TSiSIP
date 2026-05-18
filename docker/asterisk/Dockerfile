FROM debian:bookworm-slim@sha256:67b30a61dc87758f0caf819646104f29ecbda97d920aaf5edc834128ac8493d3 AS builder

ARG ASTERISK_VERSION=20.9.3

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    curl \
    ca-certificates \
    libssl-dev \
    libncurses5-dev \
    libnewt-dev \
    libxml2-dev \
    libsqlite3-dev \
    uuid-dev \
    libjansson-dev \
    libcurl4-openssl-dev \
    libedit-dev \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL \
    "https://github.com/asterisk/asterisk/releases/download/${ASTERISK_VERSION}/asterisk-${ASTERISK_VERSION}.tar.gz" \
    -o /tmp/asterisk.tar.gz \
    && tar -xzf /tmp/asterisk.tar.gz -C /usr/src \
    && cd "/usr/src/asterisk-${ASTERISK_VERSION}" \
    && ./configure --prefix=/usr/local --with-jansson-bundled \
    && make -j$(nproc) \
    && make install \
    && make samples \
    && mkdir -p /etc/asterisk \
    && cp -r /usr/local/etc/asterisk/* /etc/asterisk/ 2>/dev/null || true \
    && rm -rf /tmp/asterisk.tar.gz "/usr/src/asterisk-${ASTERISK_VERSION}"

# Runtime stage
FROM debian:bookworm-slim@sha256:67b30a61dc87758f0caf819646104f29ecbda97d920aaf5edc834128ac8493d3

RUN apt-get update && apt-get install -y --no-install-recommends \
    libssl3 \
    libncurses6 \
    libnewt0.52 \
    libxml2 \
    libsqlite3-0 \
    uuid-runtime \
    libjansson4 \
    libcurl4 \
    libedit2 \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=builder /usr/local /usr/local
RUN mkdir -p /etc/asterisk
COPY --from=builder /etc/asterisk /etc/asterisk

# Copy TSiSIP-specific Asterisk configuration
COPY docker/asterisk/pjsip.conf /etc/asterisk/pjsip.conf
COPY docker/asterisk/extensions.conf /etc/asterisk/extensions.conf

# Copy health check script
COPY healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Health check
HEALTHCHECK --interval=15s --timeout=5s --start-period=25s --start-interval=5s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh || exit 1

EXPOSE 5060/udp 5060/tcp 5038/tcp

CMD ["asterisk", "-f", "-vvv"]
