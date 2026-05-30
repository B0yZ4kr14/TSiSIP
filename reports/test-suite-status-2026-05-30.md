# TSiSIP Test Suite Status — 2026-05-30

## Summary

All **fast tests** (no Docker stack required) are **passing** both locally and on the VPS.

## Fast Test Suite (`tests/run-fast-tests.sh`)

| Test | Local | VPS |
|------|-------|-----|
| Accessibility Audit | ✅ PASS | ✅ PASS |
| D3.js + jQuery Coexistence | ✅ PASS | ✅ PASS |
| OCP Critical Pages (23 tests) | ✅ PASS | ✅ PASS |
| Requirement ID Format | ✅ PASS | ✅ PASS |
| OCP Smoke Test (111 tests) | ✅ PASS | ✅ PASS |
| OCP MI Export | ✅ PASS | ✅ PASS |
| OCP MI Whitelist | ✅ PASS | ✅ PASS |
| OCP New Pages | ✅ PASS | ✅ PASS |
| OCP SSE Stream | ✅ PASS | ✅ PASS |

**Total: 9/9 passing**

## Fixes Applied Today

### 1. Accessibility Audit (7 violations → 0)

**Files modified:**
- `web/common/header.php`: Added `type="button"` and `aria-label` to bookmark button
- `web/dispatcher.php`: Added `type="button"` to 6 action buttons (add, reload, edit, probe, delete, rollback)

### 2. Requirement ID Format (9 violations → 0)

**File modified:**
- `tests/integration/test-requirement-id-format.js`: Fixed regex to exclude `NFR-XXX` from flat ID detection

  ```diff
  - const flatPattern = /FR-(\d{3})(?!-\d{3})/g;
  + const flatPattern = /(?<![A-Z])FR-(\d{3})(?!-\d{3})/g;
  ```

## Integration Tests (Require Running Stack)

The full integration test suite (`tests/run-integration-suite.sh`) contains 30+ pytest tests and container-network tests. These require:
- Docker Compose stack running
- Environment variables: `TSISIP_OCP_ADMIN_PASSWORD`, `TSISIP_TEST_PASSWORD`
- Clean OpenSIPS ban_list (preflight restart included)

## Running the Fast Test Suite

```bash
# Locally
bash tests/run-fast-tests.sh

# On VPS
ssh tsi@179.190.15.116 "cd /opt/tsisip && bash tests/run-fast-tests.sh"
```

---

*Report generated after Feature 038 deployment and maintenance cleanup.*
