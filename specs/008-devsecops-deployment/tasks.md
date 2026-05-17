# Tasks: DevSecOps Deployment Automation

## Phase 1 — Secret Discovery & Validation

### [x] T1.1: Enhance discover-and-secrets.sh with validation modes
**Description**: Add --check-only flag, validate SSH key format (Ed25519), add secret rotation warnings (90 days), improve error messages.
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: ./deploy/scripts/discover-and-secrets.sh --check-only returns 0 when all secrets present.

### [x] T1.2: Add secret scope separation documentation
**Description**: Document in deploy/README.md the separation between deploy secrets (GitHub, SSH) and operational secrets (TSiHomeLab vault).
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: README clearly explains which secrets are used for what.

## Phase 2 — GitHub Repository Automation

### [x] T2.1: Add --dry-run mode to github-init-repo.sh
**Description**: Add --dry-run flag that validates token permissions without creating repo. Add token scope validation (repo, delete_repo).
**Phase**: 2
**Depends on**: T1.2
**Parallel**: No
**Acceptance**: ./deploy/scripts/github-init-repo.sh --dry-run shows what would be created.

### [x] T2.2: Add repository settings verification
**Description**: After creation, verify settings: private=true, auto_init=true, gitignore_template=Docker. Fix if mismatched.
**Phase**: 2
**Depends on**: T2.1
**Parallel**: No
**Acceptance**: Script idempotently ensures correct settings.

## Phase 3 — Ansible Docker Orchestration

### [x] T3.1: Create playbook-hardening.yml
**Description**: New Ansible playbook for server hardening: firewall (ufw), fail2ban, unattended-upgrades, Docker rootless option.
**Phase**: 3
**Depends on**: T2.2
**Parallel**: No
**Acceptance**: ansible-playbook --syntax-check passes.

### [x] T3.2: Enhance playbook-deploy.yml with pre-flight checks
**Description**: Add pre-flight tasks: disk space check (>1GB), Docker daemon reachable, network connectivity test. Add no_log: true to sensitive tasks.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: No
**Acceptance**: Playbook fails fast with clear message if pre-flight checks fail.

### [x] T3.3: Add docker-compose validation to deploy
**Description**: Before starting stack, run docker compose config validation. Add docker system prune --volumes warning.
**Phase**: 3
**Depends on**: T3.2
**Parallel**: No
**Acceptance**: Invalid compose file stops deploy before any changes.

## Phase 4 — Reverse Proxy Hardening

### [x] T4.1: Enhance Nginx config with additional security headers
**Description**: Add Strict-Transport-Security, Permissions-Policy, Content-Security-Policy headers. Add OCSP stapling.
**Phase**: 4
**Depends on**: T3.3
**Parallel**: No
**Acceptance**: securityheaders.com scan returns A+ grade.

### [x] T4.2: Add Nginx health check and monitoring
**Description**: Add /nginx_status location (restricted), error page customization, access log rotation config.
**Phase**: 4
**Depends on**: T4.1
**Parallel**: No
**Acceptance**: curl http://localhost/nginx_status returns active connections.

## Phase 5 — Audit & Validation

### [x] T5.1: Complete Popper falsification tests
**Description**: Add executable test scripts for each SPoF scenario in deploy/audit/tests/. Each test must verify both failure and fallback.
**Phase**: 5
**Depends on**: T4.2
**Parallel**: No
**Acceptance**: All SPoF tests pass (exit 0).

### [x] T5.2: Create deployment validation script
**Description**: Single script deploy/validate.sh that runs all checks: secret discovery, Ansible syntax, Nginx config, health check, audit completeness.
**Phase**: 5
**Depends on**: T5.1
**Parallel**: No
**Acceptance**: ./deploy/validate.sh returns 0 when deployment is valid.

### [x] T5.3: Update Makefile with new targets
**Description**: Add targets: hardening, validate, dry-run, test-spof. Update help text.
**Phase**: 5
**Depends on**: T5.2
**Parallel**: No
**Acceptance**: make help shows all targets. make validate runs validation.

### [x] T5.4: Final documentation update
**Description**: Update deploy/README.md with complete usage instructions, architecture diagram, and troubleshooting guide.
**Phase**: 5
**Depends on**: T5.3
**Parallel**: No
**Acceptance**: README is self-contained; new operator can deploy without external docs.
