FROM python:3.11-slim@sha256:2c285c669cc837aa3bcf1af23ea1932b7b5214f9c9d3aad22417446ad91cb4fb

RUN apt-get update && \
    apt-get upgrade -y --no-install-recommends && \
    apt-get install -y --no-install-recommends openssl curl ca-certificates && \
    rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir prometheus_client==0.20.0

WORKDIR /app
COPY exporter.py /app/exporter.py

RUN groupadd -r certbot-exporter && \
    useradd -r -g certbot-exporter certbot-exporter && \
    mkdir -p /var/lib/certbot-exporter && \
    chown certbot-exporter:certbot-exporter /var/lib/certbot-exporter

USER certbot-exporter

EXPOSE 9101

# Health check — verify metrics endpoint responds with certbot data
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS http://localhost:9101/metrics | grep -q "certbot" || exit 1

ENTRYPOINT ["python3", "/app/exporter.py"]
