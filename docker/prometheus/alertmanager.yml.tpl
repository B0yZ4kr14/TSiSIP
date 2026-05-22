global:
  smtp_smarthost: '${ALERTMANAGER_SMTP_HOST:-localhost:25}'
  smtp_from: '${ALERTMANAGER_SMTP_FROM:-alerts@tsiapp.io}'
  smtp_auth_username: '${ALERTMANAGER_SMTP_USER:-}'
  smtp_auth_password: '${ALERTMANAGER_SMTP_PASS:-}'
  smtp_require_tls: false

  # Resolve timeout
  resolve_timeout: 5m

# Inhibition rules: suppress warning alerts if critical is firing
inhibit_rules:
  - source_match:
      severity: 'critical'
    target_match:
      severity: 'warning'
    equal: ['alertname', 'instance']

# Route tree
route:
  group_by: ['alertname', 'severity']
  group_wait: 30s
  group_interval: 5m
  repeat_interval: 4h
  receiver: 'default'
  routes:
    - match:
        severity: critical
      receiver: 'critical-webhook'
      group_wait: 10s
      repeat_interval: 1h
    - match:
        severity: warning
      receiver: 'default'
      group_wait: 30s

receivers:
  - name: 'default'
    webhook_configs:
      - url: '${ALERTMANAGER_WEBHOOK_URL:-http://localhost:5000/alerts}'
        send_resolved: true

  - name: 'critical-webhook'
    webhook_configs:
      - url: '${ALERTMANAGER_WEBHOOK_URL:-http://localhost:5000/alerts}'
        send_resolved: true
