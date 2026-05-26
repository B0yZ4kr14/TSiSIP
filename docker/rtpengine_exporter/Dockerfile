FROM python:3.12-alpine

WORKDIR /app
COPY exporter.py /app/exporter.py

EXPOSE 8080

ENV PYTHONUNBUFFERED=1
ENV RTPENGINE_IP=rtpengine
ENV RTPENGINE_PORT=22222
ENV EXPORTER_PORT=8080
ENV CACHE_TTL=5

HEALTHCHECK --interval=15s --timeout=5s --start-period=5s --retries=3 \
    CMD wget -qO- http://localhost:8080/metrics | grep -q 'rtpengine_up'

ENTRYPOINT ["python3", "/app/exporter.py"]
