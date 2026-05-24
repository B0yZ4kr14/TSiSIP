# Trivy Container Scan Evidence

**Date**: 2026-05-23
**Scope**: All 8 vps-lite images
**Tool**: Trivy (https://github.com/aquasecurity/trivy)

---

## Execution

```bash
bash scripts/generate-trivy-evidence.sh
```

## Images Scanned

| Image | Tag | Status |
|---|---|---|
| opensips | test | [PENDING] |
| rtpengine | test | [PENDING] |
| postgres | test | [PENDING] |
| ocp | test | [PENDING] |
| asterisk | test | [PENDING] |
| backup | test | [PENDING] |
| certbot | test | [PENDING] |
| certbot-exporter | test | [PENDING] |

## Results

| Severity | Count | Remediation Required |
|---|---|---|
| CRITICAL | [PENDING] | [PENDING] |
| HIGH | [PENDING] | [PENDING] |
| MEDIUM | [PENDING] | [PENDING] |
| LOW | [PENDING] | [PENDING] |

**Evidence Files**: `docs/security/evidence/022-vps-go-live/trivy-*.json`
