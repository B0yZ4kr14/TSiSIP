FROM debian:bookworm-slim AS builder
# Production CI must pin this base image to a digest:
# FROM debian:bookworm-slim@sha256:<current-digest>

ARG OPENSIPS_VERSION=3.6
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    ca-certificates git \
    gcc make bison flex \
    libpq-dev libssl-dev \
    pkg-config libncurses-dev \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /usr/src
RUN git clone --depth 1 --branch ${OPENSIPS_VERSION} https://github.com/OpenSIPS/opensips.git

WORKDIR /usr/src/opensips
# Build with standard modules including db_postgres, auth, dialog, dispatcher, rtpengine, topology_hiding
RUN make all include_modules="db_postgres auth auth_db dialog dispatcher rtpengine topology_hiding permissions sqlops rr tm maxfwd sipmsgops signaling sl proto_udp proto_tcp pike ratelimit userblacklist htable tls_mgm tls_openssl proto_tls" \
 && make prefix=/usr/local install include_modules="db_postgres auth auth_db dialog dispatcher rtpengine topology_hiding permissions sqlops rr tm maxfwd sipmsgops signaling sl proto_udp proto_tcp pike ratelimit userblacklist htable tls_mgm tls_openssl proto_tls"

# --- Runtime image ---
FROM debian:bookworm-slim
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates gettext-base libpq5 libssl3 netcat-openbsd procps \
 && rm -rf /var/lib/apt/lists/*

COPY --from=builder /usr/local/sbin/opensips /usr/local/sbin/opensips
COPY --from=builder /usr/local/lib64/opensips /usr/local/lib64/opensips
COPY --from=builder /usr/local/etc/opensips /usr/local/etc/opensips
COPY --from=builder /usr/local/share/opensips /usr/local/share/opensips

RUN mkdir -p /etc/opensips
COPY opensips/opensips.cfg.tpl /etc/opensips/opensips.cfg.tpl
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 5060/udp 5060/tcp 5061/tcp
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/local/sbin/opensips", "-F", "-f", "/etc/opensips/opensips.cfg"]

# --- Health Check ---
COPY docker/healthcheck/opensips-health.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh
HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh || exit 1
