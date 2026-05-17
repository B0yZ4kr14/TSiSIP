# TSiSIP DevSecOps Deployment Makefile
# Provides convenient targets for deployment operations

.PHONY: help secrets check deploy hardening validate audit test-spof clean

help:
	@echo "TSiSIP DevSecOps Deployment Targets"
	@echo "===================================="
	@echo "  secrets    - Discover and validate secrets"
	@echo "  check      - Dry-run Ansible playbook"
	@echo "  deploy     - Full deployment to TSiAPP"
	@echo "  hardening  - Run server hardening playbook"
	@echo "  validate   - Run all validation checks"
	@echo "  audit      - Run security audit checks"
	@echo "  test-spof  - Run SPoF falsification tests"
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

hardening:
	@echo "[HARDENING] Running server hardening..."
	@cd ansible && ansible-playbook -i inventory.yml playbook-hardening.yml

validate:
	@echo "[VALIDATE] Running deployment validation..."
	@./validate.sh

audit:
	@echo "[AUDIT] Running security audit..."
	@echo "1. Checking for exposed secrets in logs..."
	@grep -r "GITHUB_TOKEN\|TSiAPP_HOST\|TSiAPP_USER" /var/log/ 2>/dev/null | wc -l | xargs -I {} echo "   Found {} potential exposures in /var/log"
	@echo "2. Verifying script permissions..."
	@ls -la scripts/*.sh | awk '{print "   " $$1 " " $$9}'
	@echo "3. Audit document: deploy/audit/DEVSECOPS-AUDIT.md"
	@echo "4. SPoF tests: deploy/audit/tests/"

test-spof:
	@echo "[TEST] Running SPoF falsification tests..."
	@for test in audit/tests/test-spof-*.sh; do \
		if [ -f "$$test" ]; then \
			echo "Running: $$test"; \
			cd audit/tests && bash $$(basename $$test) && cd ../.. || exit 1; \
		fi \
	done
	@echo "[TEST] All SPoF tests passed"

clean:
	@echo "[CLEAN] Removing temporary files..."
	@rm -f /tmp/tsisip-secrets.*
	@rm -f /tmp/.env.bak.*
	@find . -name "*.tmp" -delete 2>/dev/null || true
	@find audit/tests -name "test-*" -type d -exec rm -rf {} + 2>/dev/null || true
