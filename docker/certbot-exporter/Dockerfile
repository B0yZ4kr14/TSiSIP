FROM python:3.11-slim@sha256:fc39d2e51b52f039abb0401539e852925e9750064927f5a45ab937fd1e25e8cf

RUN apt-get update && \
    apt-get install -y --no-install-recommends openssl curl ca-certificates && \
    rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir prometheus_client==0.20.1

WORKDIR /app
COPY exporter.py /app/exporter.py

RUN groupadd -r certbot-exporter && \
    useradd -r -g certbot-exporter certbot-exporter && \
    mkdir -p /var/lib/certbot-exporter && \
    chown certbot-exporter:certbot-exporter /var/lib/certbot-exporter

USER certbot-exporter

EXPOSE 9101

ENTRYPOINT ["python3", "/app/exporter.py"]
