# TSiSIP CA Tool Container
# Generates and manages PKI infrastructure for SIP TLS

FROM alpine:3.19@sha256:6baf43584bcb78f2e5847d1de515f23499913ac9f12bdf834811a3145eb11ca1

RUN apk add --no-cache \
    openssl \
    bash \
    jq

WORKDIR /ca

# Copy scripts
COPY ca-init.sh /usr/local/bin/
COPY cert-gen.sh /usr/local/bin/
COPY cert-rotate.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/*.sh

# Create CA directory structure
RUN mkdir -p /ca/root/private /ca/root/certs /ca/root/crl \
    /ca/intermediate/private /ca/intermediate/certs /ca/intermediate/crl \
    /ca/output

VOLUME ["/ca/output", "/ca/secrets"]

# Health check — verify CA directory structure and openssl availability
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD test -d /ca/root/private && test -d /ca/output && openssl version > /dev/null || exit 1

CMD ["bash"]
