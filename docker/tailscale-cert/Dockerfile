FROM tailscale/tailscale@sha256:dbeff02d2337344b351afac203427218c4d0a06c43fc10a865184063498472a6

USER root
RUN apk add --no-cache curl openssl

COPY renew.sh /usr/local/bin/renew.sh
RUN chmod +x /usr/local/bin/renew.sh

ENTRYPOINT ["/usr/local/bin/renew.sh"]
