#!/bin/sh
set -euo pipefail

# Render prometheus.yml from template using envsubst
envsubst < /etc/prometheus/prometheus.yml.tpl > /etc/prometheus/rendered/prometheus.yml

# Validate config before starting
if ! promtool check config /etc/prometheus/rendered/prometheus.yml; then
    echo "ERROR: Prometheus config validation failed"
    exit 1
fi

# Validate alert rules
if ! promtool check rules /etc/prometheus/alert-rules.yml; then
    echo "ERROR: Alert rules validation failed"
    exit 1
fi

# Start Prometheus with rendered config
exec /bin/prometheus "$@"
