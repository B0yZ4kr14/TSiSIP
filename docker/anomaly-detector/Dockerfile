# TSiSIP Anomaly Detection Sidecar
# Monitors OpenSIPS events for distributed attack detection

FROM python:3.12-slim@sha256:401f6e1a67dad31a1bd78e9ad22d0ee0a3b52154e6bd30e90be696bb6a3d7461

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Create non-root user
RUN groupadd -r detector && useradd -r -g detector detector
RUN chown -R detector:detector /app

COPY --chown=detector:detector detector.py .
COPY --chown=detector:detector baseline.py .

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

EXPOSE 8080

USER detector
CMD ["python", "detector.py"]
