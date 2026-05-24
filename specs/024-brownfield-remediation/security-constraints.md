# Security Constraints — Feature 024: Brownfield Remediation

## Review Status

**Reviewed**: 2026-05-24  
**Reviewer**: architecture-guard security-review extension (simulated)  
**Plan Version**: specs/024-brownfield-remediation/plan.md

---

## Constraints Found

### C1: Supply-Chain Integrity
- **Constraint**: Base images must be SHA-pinned per Architecture Constitution Framework-Specific Rules.
- **Plan Compliance**: T1 explicitly pins php base image to SHA digest.
- **Risk**: Medium — unpinned images allow tag substitution attacks.
- **Mitigation in Plan**: Trivy scan on pinned digest before commit (T1 verification).

### C2: Test Script Information Disclosure
- **Constraint**: Hard-coded Docker network IPs leak internal topology.
- **Plan Compliance**: T2 and T3 replace 172.x IPs with env-var parameterization.
- **Risk**: Low — test scripts are in repo, but dynamic discovery reduces exposure.
- **Mitigation in Plan**: TEST_IP env var with docker network inspect fallback.

### C3: Deploy Script Resilience
- **Constraint**: Static IP defaults can route traffic to wrong containers after network recreation.
- **Plan Compliance**: T4 removes static defaults entirely; fails closed on discovery failure.
- **Risk**: Low — dynamic discovery prevents accidental cross-tenant routing.

### C4: Configuration Hygiene
- **Constraint**: Incomplete env-example leads to operators guessing values or using weak defaults.
- **Plan Compliance**: T6 audits compose file and documents every variable.
- **Risk**: Low — placeholder values must not be usable as real secrets.

### C5: Healthcheck Endpoint Exposure
- **Constraint**: HEALTHCHECK commands inside Dockerfiles must not hit authenticated or sensitive endpoints.
- **Plan Compliance**: T8 uses lightweight readiness checks only (file existence, simple HTTP probe, cert validity).
- **Risk**: Low — no admin endpoints or DB queries in healthchecks.

---

## Warnings

### SEC-024-01 (LOW): certbot-exporter HEALTHCHECK port assumption
- T8 assumes certbot-exporter exposes metrics on port 8080. If the actual port differs, the healthcheck will fail.
- **Recommendation**: Verify actual exporter port before finalizing Dockerfile.

### SEC-024-02 (LOW): anomaly-detector HEALTHCHECK complexity
- T8 proposes a Python import healthcheck. If the detector has heavy startup, this may fail transiently.
- **Recommendation**: Use a simple file-based or HTTP-based probe instead of importing the module.

### SEC-024-03 (INFO): No new auth or trust boundary changes
- This feature is purely hygiene/config; no new authentication flows or authorization policies are introduced.
- **Impact**: Security review surface is minimal.

---

## Security-Architecture Conflict Assessment

| Conflict ID | Description | Severity | Resolution |
|---|---|---|---|
| None | No conflicts detected between security requirements and architecture constraints | — | — |

---

## Sign-Off

**Security Review**: ✅ APPROVED  
**Blocking Findings**: 0  
**Advisory Findings**: 2 (SEC-024-01, SEC-024-02)  
**Next Step**: Proceed to architecture validation
