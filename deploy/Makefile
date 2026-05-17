# TSiSIP DevSecOps Deployment Makefile
# Provides convenient targets for deployment operations

.PHONY: help secrets check deploy audit clean

help:
	@echo "TSiSIP DevSecOps Deployment Targets"
	@echo "===================================="
	@echo "  secrets    - Discover and validate secrets"
	@echo "  check      - Dry-run Ansible playbook"
	@echo "  deploy     - Full deployment to TSiAPP"
	@echo "  audit      - Run security audit checks"
	@echo "  clean      - Remove temporary files"

secrets:
	@echo "[DISCOVER] Validating secrets..."
	@./scripts/discover-and-secrets.sh --check-only 2>/dev/null || ./scripts/discover-and-secrets.sh

check:
	@echo "[CHECK] Running Ansible dry-run..."
	@cd ansible && ansible-playbook -i inventory.yml playbook-deploy.yml --check --diff

deploy:
	@echo "[DEPLOY] Deploying TSiSIP to TSiAPP..."
	@cd ansible && ansible-playbook -i inventory.yml playbook-deploy.yml

audit:
	@echo "[AUDIT] Running security audit..."
	@echo "1. Checking for exposed secrets in logs..."
	@grep -r "GITHUB_TOKEN\|TSiAPP_HOST\|TSiAPP_USER" /var/log/ 2>/dev/null | wc -l | xargs -I {} echo "   Found {} potential exposures in /var/log"
	@echo "2. Verifying script permissions..."
	@ls -la scripts/*.sh | awk '{print "   " $$1 " " $$9}'
	@echo "3. Audit document: deploy/audit/DEVSECOPS-AUDIT.md"

clean:
	@echo "[CLEAN] Removing temporary files..."
	@rm -f /tmp/tsisip-secrets.*
	@find . -name "*.tmp" -delete 2>/dev/null || true
