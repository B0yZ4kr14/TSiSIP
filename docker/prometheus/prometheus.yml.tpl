global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    cluster: tsisip
    replica: '{{.ExternalURL}}'

alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - alertmanager:9093

rule_files:
  - /etc/prometheus/alert-rules.yml

scrape_configs:
  # OpenSIPS Exporter
  - job_name: 'opensips'
    scrape_interval: 15s
    static_configs:
      - targets: ['opensips-exporter:9442']
    metrics_path: /metrics

  # RTPengine — TODO: add rtpengine-exporter sidecar when metrics endpoint is implemented
  # - job_name: 'rtpengine'
  #   scrape_interval: 30s
  #   static_configs:
  #     - targets: ['rtpengine:8080']
  #   metrics_path: /metrics

  # PostgreSQL (via postgres_exporter sidecar)
  - job_name: 'postgres'
    scrape_interval: 30s
    static_configs:
      - targets: ['postgres-exporter:9187']
    metrics_path: /metrics

  # Host metrics (node_exporter)
  - job_name: 'node'
    scrape_interval: 30s
    static_configs:
      - targets: ['node-exporter:9100']
    metrics_path: /metrics

  # Certbot Exporter
  - job_name: 'certbot'
    scrape_interval: 60s
    static_configs:
      - targets: ['certbot-exporter:9101']
    metrics_path: /metrics

  # Backup Metrics Exporter
  - job_name: 'backup'
    scrape_interval: 60s
    static_configs:
      - targets: ['backup:9101']
    metrics_path: /

  # Prometheus self-monitoring
  - job_name: 'prometheus'
    scrape_interval: 15s
    static_configs:
      - targets: ['localhost:9090']
