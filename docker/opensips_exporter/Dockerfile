FROM python:3.11-slim-bookworm@sha256:cd67330292a51e2963156f74ff340455d66b2172e9190e99f40dff9357471177

WORKDIR /app

# Install openssl for certificate expiry parsing (T3.2)
RUN apt-get update && apt-get install -y --no-install-recommends openssl curl && rm -rf /var/lib/apt/lists/*

# Install dependencies
RUN pip install --no-cache-dir \
    prometheus-client==0.20.0 \
    requests==2.32.3

# Create non-root user
RUN groupadd -r exporter && useradd -r -g exporter exporter
RUN chown -R exporter:exporter /app

# Copy exporter
COPY --chown=exporter:exporter exporter.py /app/exporter.py

EXPOSE 9442

HEALTHCHECK --interval=15s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsSL http://localhost:9442/metrics | grep -q 'opensips' || exit 1

USER exporter
CMD ["python", "/app/exporter.py"]
