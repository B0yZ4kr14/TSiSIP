# TSiSIP Canonical Project Specification

## 1. Canonical definition

TSiSIP is a Docker-image-first SIP edge-proxy platform. Its SIP engine is based on OpenSIPS 3.6 LTS. Its purpose is to operate as the only public SIP signaling entry point and security boundary for a private multi-tenant Asterisk PBX backend cluster.

All OpenSIPS version references in this document refer exclusively to OpenSIPS 3.6 LTS. Changing the LTS baseline requires a documented architecture decision before any version reference, module list, Docker package, or DB schema is changed.

The canonical runtime stack is:

- TSiSIP SIP edge service running from a project-owned Docker image.
- PostgreSQL as the canonical relational database.
- RTPengine as the canonical media relay.
- Asterisk PBX nodes as private backend targets.
- Docker networks enforcing separation between public edge, SIP internals, and database internals.

No TSiSIP design, documentation, or implementation should introduce direct public network routes to Asterisk or PostgreSQL.

## 2. Non-negotiable architecture rules

| Rule | Canonical requirement |
|---|---|
| Edge topology | The TSiSIP SIP edge service is the only public SIP signaling endpoint. |
| Container delivery | The TSiSIP SIP edge service is built and delivered as a project-owned Docker image. |
| Database | PostgreSQL is mandatory. Use `db_postgres`, PostgreSQL DSNs, and PostgreSQL DDL. |
| Backend isolation | Asterisk nodes are private Docker-network services with no host port publishing. |
| Media masking | RTP is relayed through RTPengine; backend RTP addresses must not be exposed externally. |
| Authentication | SIP requests from untrusted sources must be authenticated at the TSiSIP SIP edge before routing. |
| Routing | Backend selection is dynamic and data-driven, using authenticated tenant context and routing headers. |
| Secrets | Runtime secrets must be injected through Docker secrets or environment-templated config, never committed. |
| Module validity | Only OpenSIPS modules documented for the selected OpenSIPS LTS may be referenced. |

## 3. Authoritative technology baseline

| Component | Canonical choice |
|---|---|
| SIP proxy | TSiSIP SIP edge service (OpenSIPS 3.6 LTS engine) |
| Database | PostgreSQL |
| Media relay | RTPengine |
| PBX backend | Asterisk nodes, isolated per tenant or routing group |
| Packaging | Docker image + Docker Compose or equivalent container orchestrator |
| SIP signaling | `5060/udp`, `5060/tcp` |
| SIP signaling (TLS) | `5061/tcp` |
| RTP media | `10000-20000/udp` |

OpenSIPS facts must be validated against:

- `https://www.opensips.org/Documentation/Manuals`
- `https://www.opensips.org/About/AvailableVersions`
- `https://www.opensips.org/Documentation/Manual-3-6`
- `https://www.opensips.org/Documentation/Modules-3-6`
- `https://opensips.org/docs/modules/3.6.x/<module>.html`

Protocol facts must be validated against IETF/RFC sources.

## 4. System architecture

```text
Internet / SIP clients
        |
        | 5060/udp, 5060/tcp
        v
+-----------------------------+
| TSiSIP SIP edge service     |
| OpenSIPS 3.6 engine         |
| - auth                      |
| - header routing            |
| - topology hiding           |
| - dispatcher failover       |
+-------------+---------------+
              |
              | internal SIP control
              v
+-----------------------------+
| Asterisk PBX backends       |
| private Docker network only |
+-----------------------------+

Internet / RTP clients
        |
        | 10000-20000/udp
        v
+-----------------------------+
| RTPengine media relay       |
| public RTP, internal control|
+-----------------------------+

TSiSIP edge
        |
        | internal DB network
        v
+-----------------------------+
| PostgreSQL                  |
| auth + routing metadata     |
+-----------------------------+
```

## 5. Docker network model

| Network | Members | External access | Purpose |
|---|---|---:|---|
| `sip_edge` | OpenSIPS, RTPengine | yes | Public SIP and RTP ingress. |
| `sip_internal` | OpenSIPS, RTPengine, Asterisk | no | Internal SIP forwarding and RTPengine control. |
| `db_internal` | OpenSIPS, PostgreSQL | no | Database access only. |
| `metrics_host` | OCP, backup, PostgreSQL | no | Loopback-only metrics exposure (Prometheus exporter, backup metrics). Optional; used in vps-lite profile for local monitoring access. |

Published ports:

```text
OpenSIPS:  5060/udp
OpenSIPS:  5060/tcp
OpenSIPS:  5061/tcp
RTPengine: 10000-20000/udp
```

Forbidden published ports:

```text
Asterisk: any
PostgreSQL: any
RTPengine control socket: any
```

`expose:` in Docker Compose is informational only. It is not a security boundary. Asterisk isolation is enforced by `sip_internal: internal: true` plus the absence of `ports:` on every `asterisk-*` service.

RTPengine ng-control (`--listen-ng`) must bind only to the `sip_internal` interface address. Binding it to `0.0.0.0` exposes the control socket on every container interface, including `sip_edge`.

## 6. OpenSIPS module baseline

Required modules:

| Module | Purpose |
|---|---|
| `db_postgres` | PostgreSQL connectivity. |
| `sqlops` | SQL lookups for TSiSIP routing metadata. |
| `sl` | Stateless replies for edge rejection paths. |
| `tm` | Stateful transactions, replies, failover, retransmission handling. |
| `rr` | Record-Route and loose-route support. |
| `maxfwd` | Max-Forwards loop protection. |
| `sipmsgops` | SIP header and message operations. |
| `signaling` | Unified reply API required by authentication flows. |
| `auth` | Digest challenge generation and nonce handling. |
| `auth_db` | PostgreSQL-backed Digest credential verification. |
| `dialog` | Dialog state for topology hiding and in-dialog behavior. |
| `dispatcher` | PBX target selection, probing, and failover. |
| `rtpengine` | RTPengine control and SDP rewriting. |
| `topology_hiding` | Internal topology concealment. |
| `permissions` | IP ACL for trusted internal gateways/trunks. |

Feature-specific modules (loaded when the corresponding feature is enabled):

| Module | Feature | Purpose |
|---|---|---|
| `pike` | Feature 006 | Per-source IP rate limiting and flood detection. |
| `ratelimit` | Feature 006 | Auth and global anomaly throttling. |
| `userblacklist` | Feature 006 | Per-user ban list for repeated auth failures. |
| `tls_mgm` | Feature 007 | TLS profile and certificate management. |
| `tls_openssl` | Feature 007 | OpenSSL-backed TLS transport. |
| `proto_tls` | Feature 007 | TLS transport protocol registration. |
| `acc` | Feature 001+ | CDR accounting for billed calls. |

Transport support:

| Transport | OpenSIPS 3.6 rule |
|---|---|
| `proto_udp` | Compiled into the binary in source builds; APT packages may load it automatically. Source builds require explicit `loadmodule "proto_udp.so"`. |
| `proto_tcp` | Compiled into the binary in source builds; APT packages may load it automatically. Source builds require explicit `loadmodule "proto_tcp.so"`. |
| `proto_tls` | Required for TLS listeners (Feature 007). Source builds require explicit `loadmodule "proto_tls.so"`. |

Optional modules:

| Module | Use only when |
|---|---|
| `drouting` | Prefix/carrier/LCR routing is required beyond dispatcher set selection. |

Non-canonical or forbidden modules:

| Module | Status |
|---|---|
| `rtpproxy` | Valid OpenSIPS 3.6 module, but not canonical for TSiSIP. Do not use unless a separate architecture decision replaces RTPengine. |
| `sanity` | Not available in OpenSIPS 3.6 LTS; absent from the official 3.6 module documentation. Do not reference it in TSiSIP configs. |

## 7. OpenSIPS initialization parameters

```cfg
socket=udp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060
socket=tcp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060

db_default_url="postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}"

modparam("auth", "nonce_expire", 30)
modparam("auth", "secret", "${AUTH_SECRET_32_CHARS}")

modparam("auth_db", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("auth_db", "calculate_ha1", 0)
modparam("auth_db", "use_domain", 1)
modparam("auth_db", "password_column", "ha1")
modparam("auth_db", "hash_column_sha256", "ha1_sha256")
modparam("auth_db", "hash_column_sha512t256", "ha1_sha512t256")
modparam("auth_db", "load_credentials", "$avp(tenant_id)=tenant_id;$avp(route_setid)=routing_group")

modparam("sqlops", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")

modparam("dispatcher", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("dispatcher", "table_name", "dispatcher")
modparam("dispatcher", "ds_ping_method", "OPTIONS")
modparam("dispatcher", "ds_ping_interval", 30)
modparam("dispatcher", "ds_probing_mode", 1)
modparam("dispatcher", "ds_probing_threshold", 2)
modparam("dispatcher", "persistent_state", 1)

modparam("rtpengine", "rtpengine_sock", "udp:${RTPENGINE_HOST}:22222")
modparam("rtpengine", "rtpengine_tout", 2)
modparam("rtpengine", "rtpengine_retr", 3)
modparam("rtpengine", "rtpengine_disable_tout", 30)

modparam("topology_hiding", "force_dialog", 1)
modparam("topology_hiding", "th_callid_passwd", "${TOPOLOGY_SECRET}")
modparam("topology_hiding", "th_callid_prefix", "TSISIP_")

modparam("tm", "fr_timeout", 5)
modparam("tm", "fr_inv_timeout", 60)
modparam("rr", "enable_double_rr", 1)
modparam("maxfwd", "max_limit", 70)
```

The canonical config must use `mf_process_maxfwd_header(70)`. RFC 3261 defines 70 as the default initial Max-Forwards value; lower values used in examples are non-canonical unless a separate traffic-engineering decision documents the accepted hop budget.

## 8. Routing logic contract

The OpenSIPS script must preserve this route flow:

1. Reject loops and oversized messages before any backend lookup.
2. Resolve in-dialog traffic through `topology_hiding_match()` or `loose_route()`.
3. Handle `CANCEL` only through transaction matching.
4. Answer unauthenticated `OPTIONS` locally without backend routing.
5. Sanitize untrusted inbound headers.
6. Authenticate at the edge.
7. Strip credentials before forwarding.
8. Resolve PBX target through PostgreSQL-backed routing metadata.
9. Create dialog state for INVITE traffic.
10. Apply topology hiding.
11. Engage RTPengine for SDP-bearing requests and replies.
12. Relay statefully with dispatcher failover.

Canonical route skeleton. TSiSIP proxies REGISTER to the selected Asterisk backend after edge authentication; OpenSIPS does not terminate registrations locally in the canonical design.
`OPTIONS` is answered locally before sanitize/auth and must never call `route(SANITIZE)`, `route(AUTH)`, or `route(HEADER_ROUTING)`.

```cfg
route {
    if (!mf_process_maxfwd_header(70)) {
        if ($retcode == -1) {
            sl_send_reply(483, "Too Many Hops");
        } else {
            sl_send_reply(500, "Max-Forwards Processing Error");
        }
        exit;
    }

    # Defensive application-level bound. RFC 3261 recommends 4096 bytes as a
    # sensible maximum for UDP; TCP may accept larger messages. Implementations
    # may tune this threshold based on transport and deployment constraints.
    if ($ml > 4096) {
        sl_send_reply(513, "Message Too Large");
        exit;
    }

    if (has_totag()) {
        if (topology_hiding_match() || loose_route()) {
            route(RELAY);
            exit;
        }
        sl_send_reply(481, "Call/Transaction Does Not Exist");
        exit;
    }

    if (is_method("CANCEL")) {
        if (t_check_trans()) route(RELAY);
        exit;
    }

    if (is_method("OPTIONS")) {
        # Intentional unauthenticated keepalive response. It must not route to
        # backends and must not expose backend topology or version details.
        sl_send_reply(200, "OK");
        exit;
    }

    route(SANITIZE);
    route(AUTH);
    route(HEADER_ROUTING);

    if (is_method("INVITE")) {
        create_dialog();
        topology_hiding("C");
        t_on_branch("BRANCH_MANAGE");
        t_on_reply("REPLY_MANAGE");
        t_on_failure("FAILURE_MANAGE");
    }

    route(RELAY);
}
```

Canonical support blocks:

```cfg
route[SANITIZE] {
    if (!is_present_hf("From") || !is_present_hf("To") ||
        !is_present_hf("Call-ID") || !is_present_hf("CSeq")) {
        sl_send_reply(400, "Bad Request");
        exit;
    }

    remove_hf("P-Asserted-Identity");
    remove_hf("P-Preferred-Identity");
    remove_hf("X-Tenant-ID");
    remove_hf("X-Backend-ID");
    remove_hf("X-Route-Override");
}

route[RELAY] {
    if (is_method("INVITE") && has_body("application/sdp")) {
        if (!rtpengine_offer("replace-origin replace-session-connection ICE=remove")) {
            sl_send_reply(500, "RTP Relay Error");
            exit;
        }
    }

    if (is_method("BYE|CANCEL")) {
        rtpengine_delete();
    }

    remove_hf("Authorization");
    remove_hf("Proxy-Authorization");

    if (!t_relay()) {
        sl_reply_error();
    }
    exit;
}

branch_route[BRANCH_MANAGE] {
    # Reserved for per-branch observability and future branch-specific RTP flags.
}

onreply_route[REPLY_MANAGE] {
    if (has_body("application/sdp") && $rs >= 183 && $rs < 300) {
        rtpengine_answer("replace-origin replace-session-connection ICE=remove");
    }

    remove_hf("Server");
    remove_hf("X-Tenant-ID");
}

failure_route[FAILURE_MANAGE] {
    # Only 401, 407, 486, and 6xx terminate failover; other relay failures may advance.
    if (t_check_status("401|407|486|6[0-9][0-9]")) exit;

    if (ds_next_dst()) {
        ds_mark_dst("p");
        t_on_reply("REPLY_MANAGE");
        t_on_failure("FAILURE_MANAGE");
        if (!t_relay()) t_reply(500, "Internal Server Error");
        exit;
    }

    rtpengine_delete();
    t_reply(503, "Service Unavailable");
}
```

## 9. Authentication contract

TSiSIP uses SIP Digest authentication backed by PostgreSQL. Plaintext passwords are not canonical and must not be stored.

Credential columns:

| Column | Algorithm |
|---|---|
| `ha1` | `MD5(username ":" realm ":" password)` |
| `ha1_sha256` | `SHA-256(username ":" realm ":" password)` |
| `ha1_sha512t256` | `SHA-512/256(username ":" realm ":" password)` |

Canonical behavior:

```cfg
route[AUTH] {
    if (is_method("REGISTER")) {
        if (!www_authorize("$td", "subscriber")) {
            www_challenge("$td", "auth", "MD5,SHA-256,SHA-512-256");
            exit;
        }
        consume_credentials();
        # Removes Authorization/Proxy-Authorization before HEADER_ROUTING and RELAY.
        return;
    }

    if (!proxy_authorize("$fd", "subscriber")) {
        proxy_challenge("$fd", "auth", "MD5,SHA-256,SHA-512-256");
        exit;
    }

    consume_credentials();
    # Removes Authorization/Proxy-Authorization before HEADER_ROUTING and RELAY.
}
```

> **Implementation note**: The current `opensips.cfg.tpl` uses `www_authorize()` for all authenticated methods, producing a `401 Unauthorized` response. The canonical contract above specifies `proxy_authorize()`/`proxy_challenge()` for non-REGISTER requests, which would produce `407 Proxy Authentication Required`. Production validation (2026-05-19) observes `401 Unauthorized` for unauthenticated INVITE. This deviation is documented pending alignment with the canonical proxy-authentication contract.

## 10. Header routing contract

Header-based routing must never trust arbitrary external headers without prior sanitization and authentication.

Canonical input priority:

1. Authenticated tenant context loaded by `auth_db`.
2. Sanitized `X-Routing-Key`, if allowed by deployment policy.
3. To-domain (`$td`).
4. Request-domain (`$rd`).
5. Subscriber default routing group.

Canonical lookup. The `X-Routing-Key` override is authorized only inside the authenticated tenant scope. Header names and tenant ownership are part of the lookup predicate.

```cfg
route[HEADER_ROUTING] {
    if ($avp(tenant_id) == "") {
        sl_send_reply(403, "Tenant Context Required");
        exit;
    }

    $var(header_name) = "to_domain";
    $var(route_key) = $td;
    if ($hdr(X-Routing-Key) != "") {
        $var(header_name) = "X-Routing-Key";
        $var(route_key) = $hdr(X-Routing-Key);
    }

    if (!sql_query_one(
        "SELECT dispatcher_setid FROM header_routing_rules WHERE enabled = true AND tenant_id = '$(avp(tenant_id){s.escape.common})' AND header_name = '$(var(header_name){s.escape.common})' AND match_type = 'exact' AND match_value = '$(var(route_key){s.escape.common})' ORDER BY priority ASC LIMIT 1",
        "$var(setid)"
    )) {
        $var(setid) = $avp(route_setid);
    }

    if ($var(setid) == "") {
        sl_send_reply(404, "No Tenant Route");
        exit;
    }

    if (!ds_select_dst($var(setid), 4, "f")) {
        sl_send_reply(503, "No Backend Available");
        exit;
    }

    remove_hf("X-Routing-Key");
    append_hf("X-Tenant-ID: $avp(tenant_id)\r\n");
}
```

## 11. RTP relay contract

OpenSIPS does not relay RTP packets directly. OpenSIPS controls RTPengine, and RTPengine relays media.

Canonical SDP handling:

| SIP message | Function |
|---|---|
| Initial INVITE with SDP | `rtpengine_offer()` |
| 183/200 reply with SDP | `rtpengine_answer()` |
| BYE/CANCEL/failure | `rtpengine_delete()` |
| Per-branch SDP handling | Non-normative; the canonical skeleton uses explicit offer/answer/delete functions. |

Canonical flags:

```cfg
replace-origin replace-session-connection ICE=remove
```

`ICE=remove` is canonical for the SIP-only PBX profile. WebRTC/ICE endpoints are out of scope for this baseline and require a separate profile with explicit RTPengine flags.

The port range `10000-20000/udp` is a deployment convention, not an RTP/RFC-mandated range. RFC 3550 specifies RTP behavior and does not define a mandatory deployment port range. The same range must be configured consistently in RTPengine, Docker publishing, and firewall/NAT policy.

## 12. PostgreSQL data model

The stock OpenSIPS 3.6 schema must be generated from the official OpenSIPS database deployment tooling. TSiSIP extends it with project-specific metadata.

Prerequisite stock OpenSIPS tables:

- `subscriber` must include `username`, `domain`, `ha1`, `ha1_sha256`, and `ha1_sha512t256` as required by `auth_db`.
- `dispatcher` must include the columns required by the configured OpenSIPS dispatcher module parameters: `id`, `setid`, `destination`, `state`, `weight`, `priority`, and `attrs`.
- Stock schema generation must complete before TSiSIP custom migrations run.

Canonical custom schema:

```sql
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(128) NOT NULL,
    sip_domain VARCHAR(255) NOT NULL UNIQUE,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

ALTER TABLE subscriber
    ADD COLUMN IF NOT EXISTS tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE RESTRICT,
    ADD COLUMN IF NOT EXISTS routing_group INTEGER NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS enabled BOOLEAN NOT NULL DEFAULT TRUE;

CREATE UNIQUE INDEX IF NOT EXISTS uq_subscriber_tenant_username_domain
    ON subscriber(tenant_id, username, domain);
CREATE INDEX IF NOT EXISTS idx_subscriber_tenant_domain
    ON subscriber(tenant_id, domain);

CREATE TABLE header_routing_rules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    header_name VARCHAR(64) NOT NULL,
    match_value VARCHAR(255) NOT NULL,
    match_type VARCHAR(16) NOT NULL DEFAULT 'exact'
        CHECK (match_type IN ('exact')),
    dispatcher_setid INTEGER NOT NULL,
    priority INTEGER NOT NULL DEFAULT 100,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (tenant_id, header_name, match_value)
);

CREATE INDEX idx_header_routing_lookup
    ON header_routing_rules(tenant_id, enabled, header_name, match_value, priority)
    WHERE enabled = true;

CREATE TABLE pbx_backends (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    dispatcher_setid INTEGER NOT NULL CHECK (dispatcher_setid > 0),
    label VARCHAR(128) NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (tenant_id, label)
);

CREATE INDEX IF NOT EXISTS idx_pbx_backends_dispatcher_setid
    ON pbx_backends(tenant_id, dispatcher_setid);

CREATE TABLE auth_audit_log (
    id BIGSERIAL PRIMARY KEY,
    event_time TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    username VARCHAR(64) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    source_ip INET NOT NULL,
    sip_method VARCHAR(16) NOT NULL,
    result VARCHAR(32) NOT NULL,
    call_id VARCHAR(255)
);
```

`pbx_backends.dispatcher_setid` maps to `dispatcher.setid`, not `dispatcher.id`. The canonical SIP URI remains in `dispatcher.destination`; `pbx_backends` stores tenant ownership and operational metadata only. Production migrations must reject tenant/backend metadata whose `dispatcher_setid` has no corresponding active `dispatcher.setid`.

## 13. Docker image contract

The OpenSIPS image must be built by the project.

Bare-metal or VM-first host installation is non-canonical. `apt-get install` instructions are allowed only inside project-owned Dockerfiles; host package installation must not be documented as the canonical runtime path.

Canonical Dockerfile baseline:

```dockerfile
FROM debian:bookworm-slim
# Production CI must pin this base image to a digest:
# FROM debian:bookworm-slim@sha256:<current-digest>

ARG OPENSIPS_VERSION=3.6
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates curl gnupg gettext-base \
 && curl -fsSL https://apt.opensips.org/opensips-org.gpg -o /usr/share/keyrings/opensips-org.gpg \
 && echo "deb [signed-by=/usr/share/keyrings/opensips-org.gpg] https://apt.opensips.org bookworm ${OPENSIPS_VERSION}-releases" \
    > /etc/apt/sources.list.d/opensips.list \
 && apt-get update \
 && apt-get install -y --no-install-recommends \
    opensips opensips-postgres-module opensips-auth-modules \
 && rm -rf /var/lib/apt/lists/*

# Expected OpenSIPS 3.6 package set. CI/release must validate names against
# the pinned apt.opensips.org bookworm/3.6-releases package index.

COPY opensips/opensips.cfg.tpl /etc/opensips/opensips.cfg.tpl
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 5060/udp 5060/tcp 5061/tcp
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/sbin/opensips", "-F", "-f", "/etc/opensips/opensips.cfg"]
```

Canonical entrypoint:

```sh
#!/bin/sh
set -eu

if [ -f /run/secrets/db_password ]; then
    DB_PASSWORD="$(cat /run/secrets/db_password)"
    export DB_PASSWORD
fi
if [ -f /run/secrets/auth_secret ]; then
    AUTH_SECRET_32_CHARS="$(cat /run/secrets/auth_secret)"
    export AUTH_SECRET_32_CHARS
fi
if [ -f /run/secrets/topology_secret ]; then
    TOPOLOGY_SECRET="$(cat /run/secrets/topology_secret)"
    export TOPOLOGY_SECRET
fi

envsubst < /etc/opensips/opensips.cfg.tpl > /etc/opensips/opensips.cfg

exec "$@"
```

## 14. Docker Compose contract

```yaml
services:
  postgres:
    image: postgres:16
    networks: [db_internal]
    environment:
      POSTGRES_DB: opensips
      POSTGRES_USER: opensips
      POSTGRES_PASSWORD_FILE: /run/secrets/db_password
    secrets: [db_password]
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./db/init:/docker-entrypoint-initdb.d:ro

  rtpengine:
    build:
      context: ./docker/rtpengine
    image: tsisip/rtpengine:latest
    networks: [sip_edge, sip_internal]
    ports:
      - "10000-20000:10000-20000/udp"
    command:
      - --interface=${RTPENGINE_PRIVATE_IP}!${HOST_PUBLIC_IP}
      - --listen-ng=udp:${RTPENGINE_INTERNAL_IP}:22222
      - --port-min=10000
      - --port-max=20000
      - --log-stderr

  opensips:
    build: .
    networks: [sip_edge, sip_internal, db_internal]
    ports:
      - "5060:5060/udp"
      - "5060:5060/tcp"
      - "5061:5061/tcp"
    environment:
      OPENSIPS_LISTEN_IP: ${OPENSIPS_LISTEN_IP}
      HOST_PUBLIC_IP: ${HOST_PUBLIC_IP}
      DB_HOST: postgres
      DB_NAME: opensips
      DB_USER: opensips
      RTPENGINE_HOST: rtpengine
      RTPENGINE_INTERNAL_IP: ${RTPENGINE_INTERNAL_IP}
    secrets: [db_password, auth_secret, topology_secret]
    depends_on: [postgres, rtpengine]
    cap_drop: [ALL]
    cap_add: [NET_BIND_SERVICE, SETUID, SETGID]
    security_opt: ["no-new-privileges:true"]

  asterisk-pbx-1:
    build:
      context: ./docker/asterisk
    image: tsisip/asterisk:latest
    networks: [sip_internal]
    # expose is informational only; isolation is enforced by sip_internal: internal: true.
    expose:
      - "5060/udp"
      - "5060/tcp"

  asterisk-pbx-2:
    image: tsisip/asterisk:latest
    networks: [sip_internal]
    # expose is informational only; isolation is enforced by sip_internal: internal: true.
    expose:
      - "5060/udp"
      - "5060/tcp"

networks:
  sip_edge:
    driver: bridge
  sip_internal:
    driver: bridge
    internal: true
  db_internal:
    driver: bridge
    internal: true

volumes:
  postgres_data:

secrets:
  db_password:
    file: ./secrets/db_password
  auth_secret:
    file: ./secrets/auth_secret
  topology_secret:
    file: ./secrets/topology_secret
```

Runtime secrets must be created locally under `./secrets/` and excluded from version control. The required secret files are:

```text
secrets/db_password
secrets/auth_secret       # exactly 32 characters for auth secret
secrets/topology_secret
```

Extended services (defined in feature specs and added to production Compose files):

- **OCP** (Feature 002): TSiSIP Control Panel web service; proxied via Nginx reverse proxy (Feature 008).
- **backup** (Feature 005): Backup sidecar for PostgreSQL logical backups, WAL archiving, and restore validation.
- **prometheus / grafana** (Feature 003): Observability stack; deferred to Phase 2 in vps-lite profiles.

Canonical Compose isolation rules:

- Only `opensips` may publish `5060/udp`, `5060/tcp`, and `5061/tcp`.
- Only `rtpengine` may publish `10000-20000/udp`.
- No `asterisk-*` service may define a `ports:` stanza or attach to a non-internal public network.
- `postgres` must not define a `ports:` stanza and must attach only to `db_internal`.
- RTPengine control must bind only to `${RTPENGINE_INTERNAL_IP}:22222`.

## 15. Health and readiness contract

| Service | Readiness evidence |
|---|---|
| `postgres` | `pg_isready -U opensips -d opensips` succeeds inside the container. |
| `rtpengine` | Internal ng-control socket `${RTPENGINE_INTERNAL_IP}:22222` is reachable from OpenSIPS network scope; RTP port range is bound. |
| `opensips` | `opensips -c -f /etc/opensips/opensips.cfg` succeeds inside the built image before runtime start. |
| `asterisk-*` | SIP listener is reachable from `sip_internal` only; no host port is published. |

See Feature 004 for container health probe definitions (SIP OPTIONS interval, timeout, retries, and start-period timing) and autohealing behavior.

## 16. Operator runbook

Required preflight sequence:

1. Create `secrets/db_password`, `secrets/auth_secret`, and `secrets/topology_secret` locally; never commit them.
2. Run `docker compose config`.
3. Run `docker compose build opensips`.
4. Inspect rendered Compose output and verify only OpenSIPS and RTPengine publish canonical host ports.
5. Validate OpenSIPS syntax inside the image with `opensips -c -f /etc/opensips/opensips.cfg`.
6. Confirm PostgreSQL readiness, RTPengine control reachability, and Asterisk SIP reachability on internal networks only.

## 17. Security model

| Surface | Requirement |
|---|---|
| SIP ingress | Authenticate before backend route selection. |
| OPTIONS keepalive | May receive unauthenticated `200 OK` only when it is answered locally and never routed to backends. |
| SIP headers | Remove untrusted identity/routing override headers before lookup; forwarded requests must not contain client-supplied backend identity or topology headers. |
| Credentials | Strip with `consume_credentials()` before forwarding. |
| Backend topology | Hide using `topology_hiding`; do not expose PBX IPs. |
| RTP | Rewrite SDP and force media through RTPengine. |
| Database | PostgreSQL network is internal only. |
| Docker runtime | Drop capabilities except those required for binding and privilege drop. |
| Secrets | Inject at runtime; do not commit. |
| TLS signaling | Encrypt SIP signaling on `5061/tcp` using mutual TLS for trusted trunks. See Feature 007 for TLS profile, cipher hardening, and certificate rotation. |
| Media encryption | Negotiate SRTP keys via RTPengine for TLS-signaled calls. See Feature 007 for SRTP cipher policies and DTLS-SRTP support. |

## 18. Documentation rules

All TSiSIP documentation must:

- Require OpenSIPS to be built and run from a committed, project-owned Dockerfile.
- Treat PostgreSQL as canonical: all persistence uses PostgreSQL DDL, `db_postgres`, and PostgreSQL DSNs.
- Reference only OpenSIPS modules, parameters, and functions that include official OpenSIPS 3.6 source URLs.
- Avoid MySQL/MariaDB alternatives unless the architecture is explicitly changed.
- Avoid direct-Asterisk exposure examples.
- Include port and network isolation assumptions when describing deployment.

## 19. Wiki System

TSiSIP includes a Professional Premium Wiki embedded in the OCP control panel:
- **Location**: `/TSiSIP/Wiki` (via `wiki.php`)
- **Source**: `docs/wiki/` markdown files
- **Renderer**: Regex-based markdown parser with TOC generation
- **Role-based navigation**: admin, devops, dentist, assistant, user, readonly
- **Pages**: System Overview, DevOps SIP, Administrators, Operators & Users, Dentists, Assistants, Security, Developers, Runbooks
- **Dashboard**: Role-aware landing page at `dashboard.php`

The wiki is deployed as part of the OCP Docker image.

### 19.1 OCP Authentication

The TSiSIP Control Panel requires authenticated access for all protected pages.

**Authentication mechanism**:
- PostgreSQL-backed `ocp_users` table with bcrypt password hashes (`crypt('password', gen_salt('bf', 12))`)
- PHP `password_verify()` for hash validation (supports `$2a$` and `$2y$` bcrypt)
- Session-based authentication with `PHPSESSID` cookie
- Account lockout after 5 failed attempts (15-minute lockout via `locked_until`)
- Audit logging to `ocp_login_log` (username, source_ip, user_agent, result, reason)

**Default administrative user**:
- Username: `Admin`
- Password: `admin123!` (must be changed on first production deploy)
- Role: `admin`

**Role hierarchy**:
| Role | Level | Accessible Pages |
|---|---|---|
| admin | 5 | All pages + Administrators + Developers |
| devops | 4 | System Overview, DevOps SIP, Runbooks, Security |
| dentist | 3 | System Overview, Operators & Users, Dentists |
| assistant | 2 | System Overview, Operators & Users, Assistants |
| user | 1 | System Overview, Operators & Users |
| readonly | 0 | System Overview, Operators & Users (read-only) |

**Dashboard sections**:
- **System Management**: Visible to `admin` and `devops` roles only. Contains links to `dispatcher.php` (Dispatcher Targets) and `rtpengine.php` (RTPengine Sessions).
- **Documentation & Wiki**: Visible to all authenticated roles. Contains role-appropriate wiki page quick links.
- **System Status**: Visible to all authenticated roles. Shows operational status of OpenSIPS, RTPengine, PostgreSQL, and OCP with colored indicators.

**Route protection**:
- `dashboard.php`, `wiki.php`, `dispatcher.php`, `rtpengine.php` require `requireAuth()`
- Unauthenticated requests redirect to `login.php`
- `logout.php` destroys session and invalidates cookie

**Database schema**:
```sql
CREATE TABLE ocp_users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    username VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'readonly'
        CHECK (role IN ('admin', 'devops', 'dentist', 'assistant', 'user', 'readonly')),
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    failed_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until TIMESTAMPTZ,
    last_login_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

**Container integration**:
- OCP container reads database password from `/tmp/db_password` (copied from Docker secret by entrypoint with www-data-readable permissions)
- PDO connection uses `pgsql:host=postgres;dbname=opensips;port=5432`

## 20. Rejected non-canonical patterns

The following patterns are explicitly rejected for TSiSIP documentation, examples, specs, and implementation snippets:

| Pattern | Canonical replacement |
|---|---|
| OpenSIPS 3.4 target baseline | OpenSIPS 3.6 LTS only; changing version requires an architecture decision. |
| `db_mysql`, MySQL, or MariaDB persistence | `db_postgres`, PostgreSQL DSNs, and PostgreSQL DDL only. |
| Bare-metal or VM-first runtime guide | Project-owned Docker images plus Compose or equivalent container orchestration. |
| Host-level `apt-get install opensips postgresql rtpengine` as runtime setup | Package installation inside Dockerfiles; PostgreSQL/RTPengine/OpenSIPS run as services in the container topology. |
| `modparam("auth_db", "calculate_ha1", 1)` | `calculate_ha1=0`; OpenSIPS reads precomputed HA1 columns. |
| `modparam("auth_db", "password_column", "password")` | `password_column="ha1"` or default; do not read plaintext passwords. |
| `password VARCHAR` population or plaintext seed data such as `strongpass123` | Populate `ha1`, `ha1_sha256`, and `ha1_sha512t256`; never store usable plaintext secrets. |
| `auth_check()` or `auth_challenge()` | OpenSIPS `www_authorize()`/`www_challenge()` for REGISTER and `proxy_authorize()`/`proxy_challenge()` for proxy authentication. |
| Authentication limited to REGISTER and INVITE only | Authenticate all non-OPTIONS untrusted requests before backend routing. |
| Hard-coded `ds_select_dst(1, ...)` for tenant routing | Derive dispatcher set from authenticated tenant-scoped PostgreSQL routing metadata. |
| `ds_select_dst(1, "4")` or `ds_select_dst(1,'4')` | Use integer algorithm argument: `ds_select_dst($var(setid), 4, "f")`. |
| Custom `CREATE TABLE subscriber` replacing stock OpenSIPS schema | Generate stock OpenSIPS 3.6 schema first, then apply TSiSIP `ALTER TABLE` tenant/routing extensions. |
| Custom `CREATE TABLE dispatcher` with `flags` | Use stock OpenSIPS 3.6 dispatcher schema; state tracking column is `state`, not `flags`. |
| Omission of `header_routing_rules`, `pbx_backends`, or tenant-scoped lookup | Use tenant-scoped routing metadata and dispatcher-set indirection. |
| Omission of `branch_route`, `onreply_route`, or `failure_route` | Use the canonical route skeleton with reply processing and dispatcher failover. |
| `topology_hiding("U")` as baseline | `topology_hiding("C")`; other flags require explicit validation and architecture decision. |
| `rtpengine_manage()` as baseline route logic | Explicit `rtpengine_offer()`, `rtpengine_answer()`, and `rtpengine_delete()` in canonical skeleton. |
| RTPengine `listen-ng=127.0.0.1` in multi-container runtime | Bind ng-control to `${RTPENGINE_INTERNAL_IP}:22222` on the internal Docker network. |
| RTPengine kernel DKMS as baseline requirement | Containerized RTPengine baseline; kernel acceleration requires separate host-capability design. |
| Forwarding `Authorization`, `Proxy-Authorization`, `X-Tenant-ID`, `X-Backend-ID`, `X-Route-Override`, or client-supplied `X-Routing-Key` | Strip credentials and untrusted routing/topology headers before relay. |

## 21. RFC reference matrix

| RFC | Role |
|---|---|
| RFC 3261 | SIP core, proxy behavior, transactions, dialogs, Digest framework. |
| RFC 3263 | SIP server location. |
| RFC 8760 | SIP Digest SHA-256 and SHA-512/256. |
| RFC 3264 | SDP offer/answer. |
| RFC 8866 | SDP. |
| RFC 3550 | RTP/RTCP. |
| RFC 3711 | SRTP. |

## 22. Canonical implementation sequence

1. Create Docker image for OpenSIPS 3.6 LTS. *(Feature 001)*
2. Add PostgreSQL schema generated from OpenSIPS 3.6 tooling. *(Feature 001)*
3. Add TSiSIP PostgreSQL extensions for tenants, routing, PBX metadata, and audit. *(Feature 001)*
4. Add `opensips.cfg.tpl` with canonical modules and route blocks. *(Feature 001)*
5. Add Docker Compose topology with `sip_edge`, `sip_internal`, and `db_internal`. *(Feature 001)*
6. Add RTPengine runtime container. *(Feature 001)*
7. Add isolated Asterisk backend services. *(Feature 001)*
8. Validate OpenSIPS config syntax inside the built image. *(Feature 001)*
9. Validate SIP auth rejection and authenticated routing. *(Feature 001)*
10. Validate RTP SDP rewrite through RTPengine. *(Feature 001)*
11. Rebrand OCP v9 as TSiSIP Control Panel with themed assets, i18n, and D3.js visualizations. *(Feature 002)*
12. Deploy Prometheus and Grafana for metrics and alerting. *(Feature 003 — Phase 2)*
13. Add container health probes, autohealing, and graceful degradation paths. *(Feature 004)*
14. Implement automated PostgreSQL backup, WAL archiving, encryption, and restore validation. *(Feature 005)*
15. Add per-source rate limiting (`pike`), auth throttling (`ratelimit`), and user blacklisting. *(Feature 006)*
16. Enable TLS signaling (`5061/tcp`), mutual TLS for trunks, and SRTP media encryption. *(Feature 007)*
17. Automate VPS provisioning, hardening, Nginx reverse proxy, and DevSecOps audit. *(Feature 008)*

## 23. Acceptance criteria

| ID | Test | Pass condition |
|---|---|---|
| AC-01 | `docker compose config` | Compose renders without errors. |
| AC-02 | `docker compose build opensips` | OpenSIPS image builds from the committed project Dockerfile without external image substitution. |
| AC-03 | Inspect Compose services | Only OpenSIPS publishes `5060/udp,tcp` and `5061/tcp`; only RTPengine publishes `10000-20000/udp`; no other service is externally reachable. |
| AC-04 | Inspect Compose services | No `asterisk-*` service contains a `ports:` stanza or attaches to a public network. |
| AC-05 | Inspect Compose services | PostgreSQL has no `ports:` stanza and is attached only to `db_internal`. |
| AC-06 | Inspect RTPengine command | `--listen-ng` is bound to `${RTPENGINE_INTERNAL_IP}:22222`, not `0.0.0.0`. |
| AC-07 | OpenSIPS config check | `opensips -c -f /etc/opensips/opensips.cfg` exits with status 0 inside the image. |
| AC-08 | Unauthenticated INVITE | External INVITE without credentials receives `401 Unauthorized` (current implementation) or `407 Proxy Authentication Required` (canonical proxy-auth contract). |
| AC-09 | Credential stripping | Forwarded SIP request to Asterisk contains no `Authorization` or `Proxy-Authorization` header. |
| AC-10 | Cross-tenant header injection | Tenant A cannot route to Tenant B by supplying `X-Routing-Key`. |
| AC-11 | Header sanitization | Forwarded request contains no client-supplied `X-Tenant-ID`, `X-Backend-ID`, or `X-Route-Override`. |
| AC-12 | RTP relay | SDP `c=` and `o=` lines are rewritten to the RTPengine public address; backend RTP addresses never appear in forwarded SDP. |
| AC-13 | RTP teardown | BYE/CANCEL or exhausted INVITE failover triggers RTPengine session deletion. |
| AC-14 | Dispatcher failover | If selected PBX fails, `ds_next_dst()` attempts the next active destination. |
| AC-15 | PostgreSQL schema | `subscriber.tenant_id` is non-null and subscriber/routing lookups include tenant-scoped predicates and indexes. |
| AC-16 | Health/readiness | PostgreSQL, RTPengine, OpenSIPS config, and internal Asterisk SIP readiness checks pass. See Feature 004 for container health probe specifications. |
| AC-17 | Legacy snippet rejection | Documentation contains no OpenSIPS 3.4 baseline, `db_mysql`, plaintext auth, host-install runtime path, or hard-coded dispatcher routing as canonical guidance. |

---

*Last Updated: 2026-05-19*
*Canonical spec version: 1.1*
