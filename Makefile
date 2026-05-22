# TSiSIP Project Makefile
# Orchestrates common development and operations tasks.

.PHONY: all build test up down lint clean ocp-build ocp-rollback help

all: build test

help:
	@echo "TSiSIP Project Targets"
	@echo ""
	@echo "  make build        - Build Docker images and OCP theme assets"
	@echo "  make test         - Run all automated tests"
	@echo "  make up           - Start the full Docker Compose stack"
	@echo "  make down         - Stop the Docker Compose stack"
	@echo "  make lint         - Validate Docker Compose and OpenSIPS config"
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
