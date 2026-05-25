# Shared Infrastructure

Items de-scoped from capabilities but critical to operations.

## Datastore
- PostgreSQL (BC-008) — Platform / SRE owns infrastructure; Operations owns schema

## Observability
- Prometheus + Grafana + Alertmanager — Platform / SRE

## Security Infrastructure
- TLS cert management (certbot, tailscale-cert) — Platform / SRE
- PKI (ca-offline) — Platform / SRE

## Resilience
- Backup & recovery — Operations / DevOps
- Health checks & autohealing — Platform / SRE

## Deployment
- Ansible, nginx, VPS scripts — Operations / DevOps
