FROM tailscale/tailscale:stable

USER root
RUN apk add --no-cache curl openssl

COPY renew.sh /usr/local/bin/renew.sh
RUN chmod +x /usr/local/bin/renew.sh

ENTRYPOINT ["/usr/local/bin/renew.sh"]
