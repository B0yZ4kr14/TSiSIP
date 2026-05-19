FROM grafana/grafana:10.4.0@sha256:f9811e4e687ffecf1a43adb9b64096c50bc0d7a782f8608530f478b6542de7d5

# Copy provisioning configurations
COPY provisioning/datasources /etc/grafana/provisioning/datasources
COPY provisioning/dashboards /etc/grafana/provisioning/dashboards
COPY provisioning/alerting /etc/grafana/provisioning/alerting

# Copy custom dashboards
COPY provisioning/dashboards/tsisip /etc/grafana/provisioning/dashboards/tsisip

# Environment variables
ENV GF_SECURITY_ADMIN_PASSWORD__FILE=/run/secrets/grafana_admin_password
ENV GF_USERS_DEFAULT_THEME=light
ENV GF_SERVER_ROOT_URL=${GRAFANA_ROOT_URL:-http://localhost:3000}
ENV GF_DASHBOARDS_DEFAULT_HOME_DASHBOARD_PATH=/etc/grafana/provisioning/dashboards/tsisip/dispatcher-health.json

# Health check
HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsSL http://localhost:3000/api/health | grep -q 'ok' || exit 1
