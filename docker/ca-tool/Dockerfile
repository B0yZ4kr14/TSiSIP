# TSiSIP CA Tool Container
# Generates and manages PKI infrastructure for SIP TLS

FROM alpine:3.19

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

CMD ["bash"]
