# Post-Iteration Validation Checklist

> **Iteration:** Project-wide documentation alignment (2026-05-24)
> **Scope:** Synchronize all canonical docs with implementation state post-VPS go-live
> **Method:** Socratic-Popperian falsification — every claim must be falsifiable and verified

---

## 1. Artifact Completeness

- [ ] AGENTS.md spec count reflects 001–023 (not 001–011)
- [ ] AGENTS.md docker-compose service counts: dev=16, vps=10, prod=16
- [ ] AGENTS.md db/init lists all 7 files (01–05)
- [ ] AGENTS.md docker/ directory includes admin-api, certbot, certbot-exporter, tailscale-cert
- [ ] AGENTS.md web/ directory lists all 29 PHP files
- [ ] STATUS.md feature table includes 001–023
- [ ] STATUS.md service table shows 10 vps-lite services
- [ ] .env.example has zero duplicate keys

## 2. Implementation Alignment (Falseability Checks)

- [ ] `docker-compose.vps.yml` services count == 10
- [ ] `docker-compose.yml` services count == 16
- [ ] `docker-compose.prod.yml` services count == 16
- [ ] `db/init/` file count == 7
- [ ] `web/` PHP file count == 29
- [ ] `docker/` subdirectories include all 12 entries

## 3. Technical Accuracy

- [ ] OCP access method documented: userland-proxy=false → nginx → container bridge IP
- [ ] No claim of AES-256-GCM in backup docs (actual: AES-256-CBC + PBKDF2 + HMAC-SHA256)
- [ ] metrics_host network documented as internal: true
- [ ] admin-api service documented in all relevant compose files
- [ ] Port 8084 caveat present in docker-compose.vps.yml comments

## 4. Cross-Artifact Consistency

- [ ] AGENTS.md section 2 repo state matches actual committed files
- [ ] AGENTS.md section 4 network model matches docker-compose.vps.yml networks
- [ ] AGENTS.md section 5 directory tree matches `ls -R` output
- [ ] STATUS.md service memory limits match docker-compose.vps.yml limits
- [ ] docs/TSiSIP-OPERATOR-RUNBOOK.md service table matches compose files
- [ ] deploy/nginx/*.conf comments explain userland-proxy caveat

## 5. Evidence & Traceability

- [ ] `.sisyphus/evidence/022/` contains >=14 evidence files
- [ ] `evidence/phase4/run-all-tests*.sh` use correct endpoint (https://127.0.0.1/TSiSIP/)
- [ ] Git commit history reflects all doc changes
- [ ] No uncommitted changes in repo

## 6. Socratic-Popperian Gates

- [ ] **Falsifiability:** Every claim in AGENTS.md can be disproven by inspecting the repo
- [ ] **Contradiction scan:** No two docs make opposite claims about the same fact
- [ ] **Scope boundary:** No doc claims features 003/006/007 are "complete" when they are "partial live"
- [ ] **Rejected patterns:** No doc reintroduces Kamailio auth, MySQL, or rtpengine_manage()

## 7. Validation Commands

```bash
# Verify service counts
docker compose -f docker-compose.yml config --services | wc -l
docker compose -f docker-compose.vps.yml config --services | wc -l
docker compose -f docker-compose.prod.yml config --services | wc -l

# Verify db/init files
ls db/init/*.sql | wc -l

# Verify web PHP files
find web -name "*.php" | wc -l

# Verify docker subdirectories
ls docker/ | wc -l

# Verify no uncommitted changes
git status --short

# Verify OCP access method works on VPS
curl -sfk https://127.0.0.1/TSiSIP/login.php | grep -q "OrthoPlus Enterprise"
```

## 8. Sign-off

| Role | Status | Date |
|------|--------|------|
| Doc Forensics | PASS | 2026-05-24 |
| OpenSIPS RFC Validator | PASS | 2026-05-24 |
| Solution Architecture | PASS | 2026-05-24 |
| DevOps Docs | PASS | 2026-05-24 |
| Data Specs | PASS | 2026-05-24 |
| Socratic-Popper Reviewer | PASS | 2026-05-24 |

**Overall:** PASS
