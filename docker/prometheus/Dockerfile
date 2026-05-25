FROM alpine:3.19@sha256:6baf43584bcb78f2e5847d1de515f23499913ac9f12bdf834811a3145eb11ca1 AS builder

RUN apk add --no-cache gettext curl

FROM prom/prometheus:v2.51.0@sha256:5ccad477d0057e62a7cd1981ffcc43785ac10c5a35522dc207466ff7e7ec845f

USER root

# Copy envsubst and libraries from builder
COPY --from=builder /usr/bin/envsubst /usr/bin/envsubst
COPY --from=builder /usr/bin/curl /usr/bin/curl
COPY --from=builder /usr/lib/libintl.so.8 /usr/lib/libintl.so.8
COPY --from=builder /lib/ld-musl-x86_64.so.1 /lib/ld-musl-x86_64.so.1
COPY --from=builder /usr/lib/libcurl.so.4 /usr/lib/libcurl.so.4
COPY --from=builder /usr/lib/libnghttp2.so.14 /usr/lib/libnghttp2.so.14
COPY --from=builder /usr/lib/libidn2.so.0 /usr/lib/libidn2.so.0
COPY --from=builder /usr/lib/libunistring.so.5 /usr/lib/libunistring.so.5
COPY --from=builder /usr/lib/libssl.so.3 /usr/lib/libssl.so.3
COPY --from=builder /usr/lib/libcrypto.so.3 /usr/lib/libcrypto.so.3
COPY --from=builder /lib/libz.so.1 /lib/libz.so.1

# Copy configuration templates
COPY prometheus.yml.tpl /etc/prometheus/prometheus.yml.tpl
COPY alert-rules.yml /etc/prometheus/alert-rules.yml
COPY alertmanager.yml.tpl /etc/prometheus/alertmanager.yml.tpl

# Create directories for rendered configs with correct permissions
RUN mkdir -p /etc/prometheus/rendered && chown nobody:nobody /etc/prometheus/rendered

# Entrypoint script for envsubst rendering
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER nobody

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsSL http://localhost:9090/-/healthy || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["--config.file=/etc/prometheus/rendered/prometheus.yml", \
     "--storage.tsdb.path=/prometheus", \
     "--storage.tsdb.retention.time=30d", \
     "--storage.tsdb.retention.size=10GB", \
     "--web.console.libraries=/usr/share/prometheus/console_libraries", \
     "--web.console.templates=/usr/share/prometheus/consoles", \
     "--web.enable-lifecycle"]
