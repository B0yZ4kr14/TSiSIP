FROM tailscale/tailscale@sha256:dbeff02d2337344b351afac203427218c4d0a06c43fc10a865184063498472a6

USER root
RUN apk add --no-cache curl openssl

COPY renew.sh /usr/local/bin/renew.sh
RUN chmod +x /usr/local/bin/renew.sh

HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD test -f /certs/live/server.crt && openssl x509 -in /certs/live/server.crt -noout -checkend 86400 || exit 1

ENTRYPOINT ["/usr/local/bin/renew.sh"]
