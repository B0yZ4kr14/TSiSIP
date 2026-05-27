.PHONY: help build up down logs test backup restore monitor clean

help:
	@echo "TSiSIP Makefile"
	@echo ""
	@echo "Targets:"
	@echo "  build     - Build all Docker images"
	@echo "  up        - Start all services"
	@echo "  down      - Stop all services"
	@echo "  logs      - View logs"
	@echo "  test      - Run integration tests"
	@echo "  backup    - Backup database"
	@echo "  restore   - Restore database"
	@echo "  monitor   - Run system monitor"
	@echo "  install   - Quick install"
	@echo "  update    - Update to latest"
	@echo "  clean     - Clean containers and volumes"

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f

test:
	bash tests/integration/test-ocp-all.sh

backup:
	bash scripts/backup-db.sh

restore:
	bash scripts/restore-db.sh

monitor:
	bash scripts/monitor.sh

install:
	bash scripts/install.sh

update:
	bash scripts/update.sh

clean:
	docker compose down -v
	docker system prune -f
