FROM debian:bookworm-slim@sha256:67b30a61dc87758f0caf819646104f29ecbda97d920aaf5edc834128ac8493d3 AS builder

ARG OPENSIPS_VERSION=3.6.6
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
 && apt-get upgrade -y --no-install-recommends \
 && apt-get install -y --no-install-recommends \
    ca-certificates git \
    gcc make bison flex \
    libpq-dev libssl-dev \
    libwebsockets-dev \
    libmicrohttpd-dev \
    libpcre2-dev \
    libcurl4-openssl-dev \
    pkg-config libncurses-dev \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /usr/src
RUN git clone --depth 1 --branch ${OPENSIPS_VERSION} https://github.com/OpenSIPS/opensips.git

WORKDIR /usr/src/opensips
# Build with standard modules including db_postgres, auth, dialog, dispatcher, rtpengine, topology_hiding
RUN make all include_modules="db_postgres auth auth_db dialog dispatcher rtpengine topology_hiding permissions sqlops rr tm maxfwd sipmsgops signaling sl proto_udp proto_tcp proto_ws proto_wss pike ratelimit userblacklist tls_mgm tls_openssl proto_tls acc httpd mi_http dialplan domain rest_client" \
 && make prefix=/usr/local install include_modules="db_postgres auth auth_db dialog dispatcher rtpengine topology_hiding permissions sqlops rr tm maxfwd sipmsgops signaling sl proto_udp proto_tcp proto_ws proto_wss pike ratelimit userblacklist tls_mgm tls_openssl proto_tls acc httpd mi_http dialplan domain rest_client"

# --- Runtime image ---
FROM debian:bookworm-slim@sha256:67b30a61dc87758f0caf819646104f29ecbda97d920aaf5edc834128ac8493d3
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
 && apt-get upgrade -y --no-install-recommends \
 && apt-get install -y --no-install-recommends ca-certificates gettext-base libpq5 libssl3 libmicrohttpd12 libpcre2-8-0 netcat-openbsd procps curl \
 && rm -rf /var/lib/apt/lists/*

COPY --from=builder /usr/local/sbin/ /usr/local/sbin/
COPY docker/opensipsctl /usr/local/sbin/opensipsctl
RUN chmod +x /usr/local/sbin/opensipsctl
COPY --from=builder /usr/local/lib64/opensips /usr/local/lib64/opensips
COPY --from=builder /usr/local/etc/opensips /usr/local/etc/opensips
COPY --from=builder /usr/local/share/opensips /usr/local/share/opensips

RUN mkdir -p /etc/opensips
COPY opensips/opensips.cfg.tpl /etc/opensips/opensips.cfg.tpl
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 5060/udp 5060/tcp 5061/tcp
# MI HTTP port (internal Docker networks only — not published to host by default)
EXPOSE 8888/tcp
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/local/sbin/opensips", "-F", "-f", "/etc/opensips/opensips.cfg", "-m", "512", "-M", "48"]

# --- Health Check ---
COPY docker/healthcheck/opensips-health.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh
HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh || exit 1
