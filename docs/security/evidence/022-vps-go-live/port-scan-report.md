# Network Port Scan Evidence

**Date**: 2026-05-23
**Scope**: All published ports in vps-lite stack

---

## Execution

```bash
bash scripts/verify-port-policy.sh
```

## Results

| Service | Expected Ports | Actual Ports | Status |
|---|---|---|---|
| OpenSIPS | 5060/udp, 5060/tcp | [PENDING] | [PENDING] |
| RTPengine | 10000-20000/udp | [PENDING] | [PENDING] |
| OCP | 8084/tcp (loopback) | [PENDING] | [PENDING] |
| Asterisk | NONE | [PENDING] | [PENDING] |
| PostgreSQL | NONE | [PENDING] | [PENDING] |
| Prometheus | NONE (vps-lite) | [PENDING] | [PENDING] |

## Verification

- [ ] Zero host-published ports for Asterisk
- [ ] Zero host-published ports for PostgreSQL
- [ ] RTPengine control socket (22222) NOT exposed to host
