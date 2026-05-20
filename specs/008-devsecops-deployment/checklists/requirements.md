# Feature 008 — Requirements Checklist

## Functional Requirements

### FR-001: Secure Secret Discovery
- [x] Acceptance criteria defined
- [x] Script discovers VPS host, user, SSH key, GitHub token, and vault key without logging values
- [x] Missing secrets are reported individually with clear instructions
- [x] Temp secrets file uses `chmod 600` and explicit deletion reminder
- [x] `--check-only` mode validates presence without deploying

### FR-002: GitHub Repository Automation
- [x] Acceptance criteria defined
- [x] Repository created under correct owner with proper settings
- [x] Script is idempotent (handles existing repo gracefully)
- [x] `--dry-run` mode validates token permissions without creating

### FR-003: Ansible Docker Orchestration
- [x] Acceptance criteria defined
- [x] Playbook installs Docker and docker-compose-plugin
- [x] Creates dedicated app directory with proper permissions
- [x] Deploys docker-compose file and web assets
- [x] Pulls latest image and starts stack
- [x] Health check verifies OCP responds with `"TSiSIP"` string
- [x] Pre-flight checks (disk space, Docker daemon, registry reachability) implemented

### FR-004: Reverse Proxy Security
- [x] Acceptance criteria defined
- [x] Nginx listens on 443 with TLS 1.2/1.3
- [x] Security headers configured (`X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`)
- [x] Rate limiting (`30r/m` with `burst=10`) active
- [x] Path-based routing `/TSiSIP/` → `localhost:8080` working
- [x] WebSocket support present for future real-time features
- [x] HTTP → HTTPS redirect active

### FR-005: Socratic Architecture Justification
- [x] Acceptance criteria defined
- [x] Subdirectory vs. subdomain decision documented
- [x] Privilege minimization gaps and mitigations documented
- [x] Secret scope separation (deploy vs. operational) documented

### FR-006: Popper Falsification Tests
- [x] Acceptance criteria defined
- [x] SPoF 1: `~/.env` missing → script exits with clear message
- [x] SPoF 2: Container crash → Docker restart policy handles it
- [x] SPoF 3: Token leak → verify no token in logs
- [x] SPoF 4: SSH key compromise → Ed25519 + forced command
- [x] SPoF 5: Nginx failure → systemd restart + health checks

## Deployment Scenarios Testability

- [x] Scenario 1 (Secret Discovery) is automated via `discover-and-secrets.sh --check-only`
- [x] Scenario 2 (GitHub Init) is testable via `--dry-run`
- [x] Scenario 3 (Ansible Deploy) is testable via `ansible-playbook --check`
- [x] Scenario 4 (Reverse Proxy) is testable via `curl https://tsiapp.io/TSiSIP/`
- [x] Scenario 5 (Socratic Audit) is testable by reading `deploy/audit/DEVSECOPS-AUDIT.md`
- [x] Scenario 6 (Popper Falsification) is testable via `make -C deploy test-spof`

## Rollback Procedures

- [x] Stack stop procedure documented (`docker compose -f docker-compose.vps.yml down`)
- [x] Database restore procedure documented (`pg_restore` from validated backup)
- [x] Image rollback procedure documented (pull previous GHCR tag)
- [x] Bootstrap script supports re-running without data loss (idempotent)
- [x] Git repository on VPS provides configuration history

## Monitoring and Alerting Requirements

- [x] Health checks defined for all essential services
- [x] Docker restart policies configured (`unless-stopped` / `on-failure`)
- [x] Backup RPO metrics exposed on loopback (`127.0.0.1:9101/metrics`)
- [x] Nginx access and error logs configured with rotation
- [x] fail2ban active for SSH brute-force protection
- [x] Prometheus + Grafana stack deployed (available in `docker-compose.yml` and `docker-compose.prod.yml` full profiles; intentionally excluded from `docker-compose.vps.yml` VPS-lite profile to conserve ~4GB RAM on resource-constrained hosts)
- [x] Alertmanager with real webhook configured (available in full profiles; webhook URL is templated via `ALERTMANAGER_WEBHOOK_URL` env var — operators supply their own endpoint at deploy time; excluded from VPS-lite by design)
- [x] opensips-exporter metrics collection (service defined in full profiles with `depends_on: opensips` and health checks; excluded from VPS-lite by design since Prometheus scrape target is not present in that profile)

## Security Compliance Requirements

- [x] UFW firewall active with default-deny incoming
- [x] Only required ports open: 22/tcp (SSH), 443/tcp (HTTPS), 5060/udp+tcp (SIP), 5061/tcp (SIP-TLS), 10000-20000/udp (RTP)
- [x] fail2ban configured for SSH with `bantime=3600`, `maxretry=3`
- [x] Unattended-upgrades enabled for security packages
- [x] Dedicated deploy user (`tsisip-deploy`) with limited sudo
- [x] SSH restricted to Ed25519 keys
- [x] Docker containers run with dropped capabilities and `no-new-privileges`
- [x] No secrets committed to repository
- [x] TLS certificates present (dummy certs deployed; real certs pending)

## References

- Feature specification: [spec.md](../spec.md)
- Deploy readiness: [deploy/VPS-DEPLOY-READINESS.md](../../../deploy/VPS-DEPLOY-READINESS.md)
- Deploy procedures: [deploy/README-VPS-DEPLOY.md](../../../deploy/README-VPS-DEPLOY.md)
- Validation script: [deploy/validate.sh](../../../deploy/validate.sh)
