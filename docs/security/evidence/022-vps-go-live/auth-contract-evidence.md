# Auth Contract Evidence — Feature 022

**Date**: 2026-05-23

---

## Evidence 1: HA1 Precomputation

```sql
-- Verify no plaintext passwords in subscriber table
SELECT COUNT(*) FROM subscriber WHERE password IS NOT NULL;
-- Expected: 0

-- Verify HA1 columns are populated
SELECT COUNT(*) FROM subscriber WHERE ha1 IS NULL;
-- Expected: 0
```

## Evidence 2: OpenSIPS Auth Configuration

```bash
# Verify calculate_ha1=0 in running config
docker compose exec opensips grep -E "calculate_ha1|password_column" /etc/opensips/opensips.cfg
# Expected: calculate_ha1=0, password_column=ha1
```

## Evidence 3: INVITE 407 Test

```bash
# Run SIP INVITE auth test
bash scripts/test-invite-407.sh
# Expected: SIP/2.0 407 Proxy Authentication Required
```

## Evidence 4: Topology Hiding

```bash
# Verify topology_hiding("C") is active
docker compose exec opensips opensipsctl mi get_statistics all | grep topology
# Expected: topology_hiding module loaded
```

## Results

| Evidence | Expected | Actual | Status |
|---|---|---|---|
| No plaintext passwords | 0 | [PENDING] | [PENDING] |
| HA1 populated | 0 nulls | [PENDING] | [PENDING] |
| calculate_ha1=0 | Found | [PENDING] | [PENDING] |
| INVITE 407 | Received | [PENDING] | [PENDING] |
| Topology hiding | Active | [PENDING] | [PENDING] |
