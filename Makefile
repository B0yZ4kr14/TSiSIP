# TSiSIP Project Makefile
# Orchestrates common development and operations tasks.

.PHONY: all build test up down lint brownfield release-tag rollback clean ocp-build ocp-rollback help

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

# Build OCP theme only
ocp-build:
	./scripts/build-ocp-theme.sh

# Rollback OCP theme
ocp-rollback:
	./scripts/rollback-ocp-theme.sh

# Clean generated artifacts
clean:
	@echo "Removing generated assets..."
	rm -f web/tsisip/css/*.css web/tsisip/js/*.js web/tsisip/assets/*.svg
	rm -f web/tsisip/asset-manifest.json
	@echo "Removing Docker artifacts..."
	docker compose down -v --remove-orphans || true
	@echo "Clean complete."
