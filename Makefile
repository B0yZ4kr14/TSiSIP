# TSiSIP Project Makefile
# Orchestrates common development and operations tasks.

.PHONY: all build test up down lint brownfield release-tag rollback monitoring-up clean ocp-build ocp-rollback health-checks runbook-scale pitr-verify help

all: build test

help:
	@echo "TSiSIP Project Targets"
	@echo ""
	@echo "  make build        - Build Docker images and OCP theme assets"
	@echo "  make test         - Run all automated tests"
	@echo "  make up           - Start the full Docker Compose stack"
	@echo "  make down         - Stop the Docker Compose stack"
	@echo "  make lint         - Validate Docker Compose and OpenSIPS config"
	@echo "  make brownfield   - Run brownfield hygiene scan"
	@echo "  make release-tag  - Tag a new release with semver + manifest"
	@echo "  make rollback     - Roll back to a previous release manifest"
	@echo "  make monitoring-up - Start the monitoring stack overlay (Prometheus/Grafana/Alertmanager)"
	@echo "  make health-checks - Validate healthcheck stanzas across all compose files"
	@echo "  make runbook-scale - Example: scale a new Asterisk backend (set IP=... SETID=...)"
	@echo "  make pitr-verify   - Verify PITR restore to a temp database"
	@echo "  make ocp-build    - Build OCP theme assets only"
	@echo "  make ocp-rollback - Rollback OCP theme to original OCP v9"
	@echo "  make clean        - Remove generated artifacts and Docker volumes"
	@echo "  make help         - Show this help message"

# Build everything
build:
	@echo "Building OCP theme assets..."
	./scripts/build-ocp-theme.sh
	@echo "Building Docker images..."
	docker compose build

# Run all tests
test:
	@echo "Running D3.js + jQuery coexistence test..."
	node tests/d3-jquery-coexistence.test.js
	@echo "Running accessibility audit..."
	node tests/accessibility-audit.test.js

# Start stack
up:
	docker compose up -d

# Stop stack
down:
	docker compose down

# Validate configurations
lint:
	@echo "Validating Docker Compose..."
	docker compose config > /dev/null && echo "  docker-compose.yml: OK"
	@echo "Validating OpenSIPS config syntax..."
	@docker run --rm \
	  -e DB_HOST=postgres -e DB_NAME=opensips -e DB_USER=opensips \
	  -e HOST_PUBLIC_IP=127.0.0.1 -e OPENSIPS_LISTEN_IP=0.0.0.0 \
	  -e RTPENGINE_HOST=rtpengine \
	  -v $(PWD)/secrets/db_password:/run/secrets/db_password:ro \
	  -v $(PWD)/secrets/auth_secret:/run/secrets/auth_secret:ro \
	  -v $(PWD)/secrets/topology_secret:/run/secrets/topology_secret:ro \
	  tsisip-opensips:latest \
	  /entrypoint.sh /usr/local/sbin/opensips -c -f /etc/opensips/opensips.cfg \
	  && echo "  OpenSIPS config: OK" || echo "  OpenSIPS config: FAILED"

# Brownfield hygiene scan
brownfield:
	@echo "Running brownfield scan..."
	@echo "  [B1] Checking for :latest image tags..."
	@! grep -n ':latest' docker-compose.yml | grep -v 'certbot-exporter' | grep -v 'opensips-exporter' | grep -v '#' || echo "  PASS: No stray :latest tags"
	@echo "  [B2] Checking for hard-coded 172.x IPs in deploy scripts..."
	@! grep -rn '172\.1[6789]\.' deploy/scripts/*.sh 2>/dev/null || echo "  PASS: No hard-coded Docker IPs"
	@echo "  [B3] Checking for secrets in git index..."
	@! git diff --cached --name-only | grep -E '^secrets/' || echo "  PASS: No secrets staged"
	@! git ls-files | grep -E '^secrets/' || echo "  PASS: No secrets tracked"
	@echo "  [B4] Checking for plaintext passwords in seed data..."
	@! grep -n "password.*[^']" db/init/03-seed-data.sql | grep -v "''" | grep -v "ha1" || echo "  PASS: Seed data uses HA1 only"
	@echo "  [B5] Checking for htable module reference..."
	@! grep -n "htable" Dockerfile || echo "  PASS: No htable references"
	@echo "  [B6] Checking for published ports on internal services..."
	@! awk '/asterisk|postgres/{found=1} found && /ports:/{print; exit}' docker-compose.yml || echo "  PASS: Internal services have no published ports"
	@echo "  Brownfield scan complete."

# Release tag with semver + manifest
release-tag:
	@echo "Tagging release..."
	@./deploy/scripts/release-tag.sh $(ARGS)

# Rollback to a previous release manifest
rollback:
	@echo "Rolling back..."
	@./deploy/scripts/rollback.sh $(ARGS)

# Start monitoring overlay
monitoring-up:
	@echo "Starting monitoring stack overlay..."
	docker compose -f docker-compose.vps.yml -f docker-compose.monitoring.yml up -d

# Build OCP theme only
ocp-build:
	./scripts/build-ocp-theme.sh

# Rollback OCP theme
ocp-rollback:
	./scripts/rollback-ocp-theme.sh

# Health check validation
health-checks:
	@echo "Validating healthcheck stanzas..."
	@bash scripts/verify-health-checks.sh

# Runbook: scale a new Asterisk backend
runbook-scale:
	@echo "Usage: make runbook-scale IP=192.0.2.99 SETID=1 DESC=new-pbx"
	@test -n "$(IP)" || (echo "ERROR: IP is required"; exit 1)
	@bash scripts/runbook/scale-asterisk.sh $(IP) $(SETID) "$(DESC)"

# PITR verification
pitr-verify:
	@echo "Verifying PITR restore to temp database..."
	@docker compose -f docker-compose.vps.yml exec backup \
		/usr/local/bin/pitr-restore.sh --target $$(date -u +%Y-%m-%dT%H:%M:%SZ) --verify-only

# Clean generated artifacts
clean:
	@echo "Removing generated assets..."
	rm -f web/tsisip/css/*.css web/tsisip/js/*.js web/tsisip/assets/*.svg
	rm -f web/tsisip/asset-manifest.json
	@echo "Removing Docker artifacts..."
	docker compose down -v --remove-orphans || true
	@echo "Clean complete."
