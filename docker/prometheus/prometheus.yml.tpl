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

  # RTPengine
  - job_name: 'rtpengine'
    scrape_interval: 30s
    static_configs:
      - targets: ['rtpengine:8080']
    metrics_path: /metrics

  # PostgreSQL (via postgres_exporter sidecar)
  - job_name: 'postgres'
    scrape_interval: 30s
    static_configs:
      - targets: ['postgres-exporter:9187']
    metrics_path: /metrics

  # Host metrics (node_exporter on Docker host)
  - job_name: 'node'
    scrape_interval: 30s
    static_configs:
      - targets: ['node-exporter:9100']
    metrics_path: /metrics

  # Prometheus self-monitoring
  - job_name: 'prometheus'
    scrape_interval: 15s
    static_configs:
      - targets: ['localhost:9090']
