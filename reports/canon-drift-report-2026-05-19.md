# TSiSIP Canonical Drift Detection Report

**Date:** 2026-05-19  
**Canonical Spec:** `docs/TSiSIP-CANONICAL-SPEC.md` (v1.1)  
**Resolution Status:** `unresolved`  
**Total Findings:** 12  

| Severity | Count |
|---|---|
| CRITICAL | 3 |
| HIGH | 4 |
| MEDIUM | 3 |
| LOW | 2 |

---

## Summary

This report compares the committed TSiSIP implementation against the canonical specification (`docs/TSiSIP-CANONICAL-SPEC.md`). The scan covers OpenSIPS configuration, Docker Compose topology, database schema, Dockerfile, and runtime entrypoints.

**Overall Assessment:** The implementation correctly adheres to the majority of canonical rules (PostgreSQL-only, `calculate_ha1=0`, `topology_hiding("C")`, explicit `rtpengine_offer/answer/delete`, no forbidden modules). However, **several critical and high-severity drifts** were detected in network topology, reply-route sanitization, failure-route behavior, and SQL injection safeguards.

---

## Findings

### CD-001 — CRITICAL: RTPengine Missing `sip_edge` Network

| Attribute | Value |
|---|---|
| **Category** | Docker Compose Topology |
| **Status** | `UNRESOLVED` |
| **Files** | `docker-compose.yml:139-140`, `docker-compose.prod.yml:60-61`, `docker-compose.vps.yml:90` |
| **Canon Ref** | Section 5 (Network Model), Section 14 (Docker Compose Contract) |

**Canonical Requirement:**  
RTPengine must attach to **both** `sip_edge` (public RTP ingress) and `sip_internal` (control plane):
```yaml
networks: [sip_edge, sip_internal]
```

**Implementation:**  
All three Compose files attach RTPengine **only** to `sip_internal`:
```yaml
networks:
  - sip_internal
```

**Impact:** Public RTP traffic (`10000-20000/udp`) is published via `ports:`, but the container lacks membership in the `sip_edge` Docker network. While host-port publishing may partially work depending on Docker bridge behavior, this violates the canonical network-separation contract and may break inter-container RTP routing expectations.

**Remediation:** Add `sip_edge` to the `rtpengine` service `networks:` array in all Compose variants.

---

### CD-002 — CRITICAL: `onreply_route` Unnamed and Missing Canonical Sanitization

| Attribute | Value |
|---|---|
| **Category** | OpenSIPS Routing Logic |
| **Status** | `UNRESOLVED` |
| **Files** | `opensips/opensips.cfg.tpl:354-367` |
| **Canon Ref** | Section 8 (Routing Logic Contract), Section 11 (RTP Relay Contract) |

**Canonical Requirement:**  
A named `onreply_route[REPLY_MANAGE]` must:
1. Run `rtpengine_answer("replace-origin replace-session-connection ICE=remove")` for SDP-bearing 183-299 replies.
2. Remove `Server` header.
3. Remove `X-Tenant-ID` header.

```cfg
onreply_route[REPLY_MANAGE] {
    if (has_body("application/sdp") && $rs >= 183 && $rs < 300) {
        rtpengine_answer("replace-origin replace-session-connection ICE=remove");
    }
    remove_hf("Server");
    remove_hf("X-Tenant-ID");
}
```

**Implementation:**  
The config uses an **unnamed** `onreply_route` that:
1. Checks `$rs =~ "^2[0-9][0-9]$"` instead of `$rs >= 183 && $rs < 300` (misses 183/180 with SDP).
2. Omits `ICE=remove` from `rtpengine_answer()`.
3. Does **not** remove `Server` or `X-Tenant-ID` headers.

```cfg
onreply_route {
    if ($rs =~ "^2[0-9][0-9]$" && has_body("application/sdp")) {
        if (!rtpengine_answer("replace-origin replace-session-connection")) { ... }
    }
}
```

**Impact:**
- Backend topology may leak via `Server` header in replies to clients.
- `X-Tenant-ID` may leak in replies.
- ICE candidates are not stripped from SDP answers, potentially causing client interoperability issues.
- 183 Session Progress with SDP is not handled for RTPengine answer.

**Remediation:** Rename to `onreply_route[REPLY_MANAGE]`, restore the canonical status-code range, add `ICE=remove`, and add `remove_hf` calls.

---

### CD-003 — CRITICAL: `failure_route` Missing Canonical Failover Behavior

| Attribute | Value |
|---|---|
| **Category** | OpenSIPS Routing Logic |
| **Status** | `UNRESOLVED` |
| **Files** | `opensips/opensips.cfg.tpl:369-385` |
| **Canon Ref** | Section 8 (Routing Logic Contract) |

**Canonical Requirement:**  
`failure_route[FAILURE_MANAGE]` must:
1. Exit on `401|407|486|6[0-9][0-9]` (do not failover).
2. Call `ds_mark_dst("p")` before attempting next destination.
3. Re-arm `t_on_reply("REPLY_MANAGE")` and `t_on_failure("FAILURE_MANAGE")`.
4. Call `rtpengine_delete()` when all destinations exhausted.
5. Reply `503 Service Unavailable` on exhaustion.

**Implementation:**  
The config defines `failure_route[FAILOVER]` that:
1. Checks `408|500|502|503|504` only — **does not block** `401|407|486|6xx` from failover.
2. Does **not** call `ds_mark_dst("p")`.
3. Does **not** re-arm `t_on_reply`.
4. Does **not** call `rtpengine_delete()` on exhaustion.
5. Does **not** send `503 Service Unavailable`; it merely logs and exits.

**Impact:**
- 401/407/486/6xx replies may incorrectly trigger dispatcher failover.
- Failed destinations are not marked as "probing", so they may be retried immediately.
- RTPengine sessions may leak when all destinations fail.
- Clients may receive no final reply on total exhaustion.

**Remediation:** Align `failure_route` name and logic with the canonical contract.

---

### CD-004 — HIGH: `route[RELAY]` Missing Canonical RTPengine Calls

| Attribute | Value |
|---|---|
| **Category** | OpenSIPS Routing Logic |
| **Status** | `UNRESOLVED` |
| **Files** | `opensips/opensips.cfg.tpl:918-937` |
| **Canon Ref** | Section 8 (Routing Logic Contract), Section 11 (RTP Relay Contract) |

**Canonical Requirement:**  
`route[RELAY]` must:
1. Call `rtpengine_offer("replace-origin replace-session-connection ICE=remove")` for INVITE with SDP.
2. Call `rtpengine_delete()` for BYE/CANCEL.
3. Strip `Authorization` and `Proxy-Authorization`.
4. Relay statefully.

**Implementation:**  
`route[RELAY]` only strips auth headers, adds `record_route()`, sets branch/failure handlers, and relays. **All RTPengine calls have been moved out** to `HANDLE_INVITE`, `SRTP_REOFFER`, and in-dialog BYE handling.

**Impact:** This is a structural drift. While the implementation does invoke RTPengine elsewhere, the canonical skeleton places these calls in `route[RELAY]` to ensure they are not omitted by future route modifications. The current dispersion increases maintenance risk.

**Remediation:** Either restore canonical `route[RELAY]` content, or document the architectural decision to move RTP handling to sub-routes and update the canonical spec.

---

### CD-005 — HIGH: SQL Injection Risk in Dynamic SQL Queries

| Attribute | Value |
|---|---|
| **Category** | Security / OpenSIPS Script |
| **Status** | `UNRESOLVED` |
| **Files** | `opensips/opensips.cfg.tpl:501-549`, `599-625`, `627-750`, `862-916` |
| **Canon Ref** | Section 8 (Routing Logic Contract), Section 10 (Header Routing Contract) |

**Canonical Requirement:**  
The canonical `HEADER_ROUTING` uses `s.escape.common` for all AVP/variable interpolations into SQL:
```cfg
sql_query_one("... tenant_id = '$(avp(tenant_id){s.escape.common})' ...")
```

**Implementation:**  
Multiple routes interpolate raw variables directly into SQL strings without escaping:
- `HEADER_ROUTING`: `$var(tenant_id)`, `$var(route_key)` — no `s.escape.common`.
- `TRUNK_VERIFY`: `$si` in `trunk_ips` lookup — no escaping.
- `TRUNK_ROUTING`: `$rd`, `$var(trunk_priority)` concatenated into query strings.
- `INBOUND_DID_ROUTING`: `$si`, `$rU` used directly.
- `TRUNK_FAILOVER`: `$var(trunk_priority)` used directly.

**Impact:** SIP message fields (Call-ID, Route-Key, R-URI user, source IP) are attacker-controlled. Without escaping, SQL injection can compromise the PostgreSQL database.

**Remediation:** Apply `$(var(...){s.escape.common})` or equivalent sanitization to all variable interpolations in SQL queries.

---

### CD-006 — HIGH: Missing `ICE=remove` in RTPengine Answer/Offer Calls

| Attribute | Value |
|---|---|
| **Category** | OpenSIPS RTP Relay |
| **Status** | `UNRESOLVED` |
| **Files** | `opensips/opensips.cfg.tpl:363`, `569`, `575`, `585`, `591`, `847`, `851`, `855` |
| **Canon Ref** | Section 11 (RTP Relay Contract) |

**Canonical Requirement:**  
Canonical SDP flags are:
```cfg
replace-origin replace-session-connection ICE=remove
```

**Implementation:**  
Multiple `rtpengine_offer()` and `rtpengine_answer()` calls omit `ICE=remove`:
- `onreply_route` line 363: `"replace-origin replace-session-connection"`
- `HANDLE_INVITE` line 575 (plain RTP): `"replace-origin replace-session-connection"`
- `SRTP_REOFFER` line 591 (plain RTP): `"replace-origin replace-session-connection"`
- `BRANCH_TRUNK_SRTP` line 855 (plain RTP): `"replace-origin replace-session-connection"`

Only the TLS/SRTP branches include SRTP-specific flags but still omit `ICE=remove` where plain RTP is used.

**Impact:** ICE candidates may be forwarded to SIP-only PBX backends or clients, causing call setup failures or unexpected NAT behavior.

**Remediation:** Add `ICE=remove` to all canonical non-WebRTC RTPengine calls.

---

### CD-007 — HIGH: Missing `persistent_state` Dispatcher Parameter

| Attribute | Value |
|---|---|
| **Category** | OpenSIPS Module Configuration |
| **Status** | `UNRESOLVED` |
| **Files** | `opensips/opensips.cfg.tpl:105-119` |
| **Canon Ref** | Section 7 (OpenSIPS Initialization Parameters) |

**Canonical Requirement:**  
```cfg
modparam("dispatcher", "persistent_state", 1)
```

**Implementation:**  
Parameter is absent.

**Impact:** Dispatcher destination state (active/inactive) is not persisted across OpenSIPS restarts. After a restart, all destinations start as "unknown" until probed.

**Remediation:** Add `modparam("dispatcher", "persistent_state", 1)`.

---

### CD-008 — MEDIUM: Header Routing Terminology Drift (`X-Route-Key` vs `X-Routing-Key`)

| Attribute | Value |
|---|---|
| **Category** | Terminology / Header Naming |
| **Status** | `UNRESOLVED` |
| **Files** | `opensips/opensips.cfg.tpl:509`, `541-542` |
| **Canon Ref** | Section 10 (Header Routing Contract) |

**Canonical Requirement:**  
The override header is named `X-Routing-Key`.

**Implementation:**  
The implementation uses `X-Route-Key` as the primary header name, while also removing `X-Routing-Key` for backward compatibility.

**Impact:** Terminology drift between spec and implementation. Future documentation, tests, or integrations may reference the wrong header name.

**Remediation:** Align implementation with canonical `X-Routing-Key` name, or update canonical spec to accept `X-Route-Key` as the official name.

---

### CD-009 — MEDIUM: `OPTIONS` Handling Inside `has_totag()` Branch

| Attribute | Value |
|---|---|
| **Category** | OpenSIPS Routing Logic |
| **Status** | `UNRESOLVED` |
| **Files** | `opensips/opensips.cfg.tpl:238-264` |
| **Canon Ref** | Section 8 (Routing Logic Contract) |

**Canonical Requirement:**  
`OPTIONS` must be answered locally at the top level of `route{}`, **before** `has_totag()`:
```cfg
if (is_method("OPTIONS")) {
    sl_send_reply(200, "OK");
    exit;
}
```

**Implementation:**  
`OPTIONS` is handled inside `if (!has_totag())`. In-dialog `OPTIONS` (with `to-tag`) falls through to the `has_totag()` branch, where it attempts `topology_hiding_match()` / `loose_route()` and may be relayed to the backend.

**Impact:** In-dialog `OPTIONS` (e.g., NAT keepalives or session timers) may be routed to Asterisk backends instead of being answered locally.

**Remediation:** Move `OPTIONS` handling to the top level, before `has_totag()`.

---

### CD-010 — MEDIUM: In-Dialog Missing Request Returns `404` Instead of `481`

| Attribute | Value |
|---|---|
| **Category** | OpenSIPS Routing Logic |
| **Status** | `UNRESOLVED` |
| **Files** | `opensips/opensips.cfg.tpl:260-262` |
| **Canon Ref** | Section 8 (Routing Logic Contract) |

**Canonical Requirement:**  
When `has_totag()` is true but neither `topology_hiding_match()` nor `loose_route()` succeeds:
```cfg
sl_send_reply(481, "Call/Transaction Does Not Exist");
```

**Implementation:**  
```cfg
sl_send_reply(404, "Not Here");
```

**Impact:** Non-standard SIP response for an in-dialog request that cannot be matched. `481` is the RFC-correct code for this condition.

**Remediation:** Change to `481`.

---

### CD-011 — LOW: `node-exporter` Attached to `sip_internal` Network

| Attribute | Value |
|---|---|
| **Category** | Docker Compose Topology |
| **Status** | `UNRESOLVED` |
| **Files** | `docker-compose.yml:107-133` |
| **Canon Ref** | Section 5 (Network Model) |

**Canonical Requirement:**  
The `sip_internal` network is reserved for SIP signaling between OpenSIPS, RTPengine, and Asterisk.

**Implementation:**  
`node-exporter` (host metrics) is attached to `sip_internal`.

**Impact:** Unnecessary exposure of host metrics scraping to the SIP-internal network scope. While not a direct security breach, it violates network-separation hygiene.

**Remediation:** Move `node-exporter` to a dedicated `metrics_host` network or host-network mode.

---

### CD-012 — LOW: Asterisk Exposes Additional Port `5038/tcp`

| Attribute | Value |
|---|---|
| **Category** | Docker Compose Topology |
| **Status** | `UNRESOLVED` |
| **Files** | `docker-compose.yml:264-265`, `docker-compose.prod.yml:582-584` |
| **Canon Ref** | Section 14 (Docker Compose Contract) |

**Canonical Requirement:**  
Asterisk services expose only `5060/udp` and `5060/tcp`.

**Implementation:**  
Asterisk services also expose `5038/tcp` (Asterisk Manager Interface / AMI).

**Impact:** Slightly expanded attack surface on the internal Docker network. `expose:` is informational only, but it documents the availability of the AMI port.

**Remediation:** Remove `5038/tcp` from `expose:` unless AMI is explicitly required by a documented feature.

---

## Verified Canonical Compliance (No Drift)

The following canonical rules were verified as **fully compliant** in the current implementation:

| Rule | Evidence |
|---|---|
| **OpenSIPS 3.6 LTS** | `Dockerfile:3` — `ARG OPENSIPS_VERSION=3.6`; builds from official git branch. |
| **No `sanity` module** | `opensips/opensips.cfg.tpl` — `sanity.so` not loaded; CI gate in `.github/workflows/deploy.yml:61` blocks it. |
| **No `db_mysql` references** | All DB DSNs use `postgres://`; `db_postgres.so` loaded. |
| **Docker-first runtime** | Project-owned `Dockerfile` at repo root; no bare-metal install docs in canonical path. |
| **PostgreSQL-only database** | `docker-compose.yml` — `postgres:16` image; `db_postgres` module; all DDL is PostgreSQL. |
| **Network names snake_case** | `sip_edge`, `sip_internal`, `db_internal`, `metrics_host`. |
| **`topology_hiding("C")`** | `opensips/opensips.cfg.tpl:564`, `:707` — uses `"C"` flag consistently. |
| **Auth `calculate_ha1=0`** | `opensips/opensips.cfg.tpl:95` — `calculate_ha1 = 0`; `password_column = "ha1"`. |
| **Auth uses `www_authorize` / `proxy_authorize`** | `opensips/opensips.cfg.tpl:440-476` — REGISTER uses `www_authorize`, non-REGISTER uses `proxy_authorize`. |
| **Header sanitization** | `route[SANITIZE]` removes `P-Asserted-Identity`, `P-Preferred-Identity`, `X-Tenant-ID`, `X-Backend-ID`, `X-Route-Override`. |
| **Credential stripping** | `route[RELAY]` removes `Authorization` and `Proxy-Authorization`. |
| **No hard-coded `ds_select_dst(1, ...)`** | Dispatcher set derived from `header_routing_rules` or subscriber `routing_group`. |
| **Stock `subscriber` + `ALTER TABLE`** | `db/init/01-stock-opensips-schema.sql` creates stock tables; `02-tsisip-extensions.sql` uses `ALTER TABLE`. |
| **Stock `dispatcher` with `state` column** | `db/init/01-stock-opensips-schema.sql:27` — column is `state`, not `flags`. |
| **RTPengine `--listen-ng` binds to internal IP** | `docker-compose.yml:152` — `--listen-ng=${RTPENGINE_INTERNAL_IP}:22222`. |
| **Asterisk has no `ports:` stanza** | Verified in all Compose files. |
| **PostgreSQL has no `ports:` stanza** | Verified in all Compose files. |
| **Secrets injected via Docker secrets** | `secrets/` directory; `.gitignore` excludes secrets; entrypoint reads from `/run/secrets/`. |
| **Capabilities dropped except required** | `cap_drop: [ALL]` with minimal `cap_add` on all services. |
| **`mf_process_maxfwd_header(70)`** | `opensips/opensips.cfg.tpl:290` — uses `70`. |

---

## Recommendations

1. **Immediate (before production deploy):**
   - Fix CD-001 (RTPengine `sip_edge` network).
   - Fix CD-002 (reply route sanitization and naming).
   - Fix CD-003 (failure route canonical behavior).
   - Fix CD-005 (SQL injection safeguards).

2. **Short-term:**
   - Fix CD-004 (restore canonical `route[RELAY]` RTP calls or document ADR).
   - Fix CD-006 (add `ICE=remove`).
   - Fix CD-007 (add `persistent_state`).

3. **Medium-term:**
   - Resolve CD-008 (header terminology alignment).
   - Fix CD-009 (OPTIONS placement).
   - Fix CD-010 (481 vs 404).

4. **Low-priority hygiene:**
   - Address CD-011 and CD-012 (network and expose cleanup).

---

*Report generated by speckit-canon-drift-detect workflow adapted for TSiSIP canonical spec verification.*
