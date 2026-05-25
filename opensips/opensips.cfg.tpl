# TSiSIP OpenSIPS 3.6 LTS Edge Proxy Configuration
# Generated from template at container startup

# --- Network listeners ---
socket=udp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060
socket=tcp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060
# TLS socket - habilitado (certificados gerados via ca-tool)
socket=tls:${OPENSIPS_LISTEN_IP}:5061 as ${HOST_PUBLIC_IP}:5061
# WebSocket listeners for WebRTC clients
socket=ws:${OPENSIPS_LISTEN_IP}:8080
socket=wss:${OPENSIPS_LISTEN_IP}:4443

# T1.3: TCP connection limits
# Global ceiling + timeouts prevent Slowloris-style connection exhaustion.
# Per-source TCP connection limiting (100/IP) is enforced at host firewall
# (iptables connlimit) when container runs with --cap-add=NET_ADMIN.
tcp_max_connections = 4096
tcp_connection_lifetime = 300
tcp_connect_timeout = 5
tcp_max_msg_time = 10

# --- Database ---
db_default_url="postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}"

mpath="/usr/local/lib64/opensips/modules/"

# M5: Explicit shared memory size (512 MB) for dialog state, pike, ratelimit tables
# NOTE: shm_mem_size is a startup parameter (-m 512) in OpenSIPS 3.6,
#        set via docker-compose deploy.resources.limits.memory (1G).
#        pkg_mem_size per process should be ~((container_limit - shm_mem_size) / children).
#        Example: (1GB - 512MB) / 8 children = ~64MB per child.
# not a config file variable. Set via CMD in Dockerfile/docker-compose.yml.

# --- Modules ---
loadmodule "db_postgres.so"
loadmodule "sqlops.so"
loadmodule "sl.so"
loadmodule "tm.so"
loadmodule "rr.so"
loadmodule "maxfwd.so"
loadmodule "sipmsgops.so"
loadmodule "signaling.so"
loadmodule "auth.so"
loadmodule "auth_db.so"
loadmodule "dialog.so"
loadmodule "dispatcher.so"
loadmodule "rtpengine.so"
loadmodule "topology_hiding.so"
loadmodule "permissions.so"
loadmodule "pike.so"
loadmodule "ratelimit.so"
loadmodule "userblacklist.so"
loadmodule "cachedb_local.so"
# TLS modules (carregados mesmo que socket TLS esteja comentado)
loadmodule "tls_mgm.so"
loadmodule "tls_openssl.so"
loadmodule "proto_udp.so"
loadmodule "proto_tcp.so"
loadmodule "proto_tls.so"
loadmodule "proto_ws.so"
loadmodule "proto_wss.so"
loadmodule "acc.so"
loadmodule "dialplan.so"
loadmodule "domain.so"

# --- BEGIN TLS ROTATION WAVE 3 ---
# httpd provides the HTTP server infrastructure; mi_http exposes the MI interface over it
loadmodule "httpd.so"
loadmodule "mi_http.so"
# --- END TLS ROTATION WAVE 3 ---

# --- BEGIN TRUNK INTEGRATION WAVE 2: Module Loading ---
# UAC modules for trunk provider registration and outbound authentication
loadmodule "uac.so"
loadmodule "uac_auth.so"
loadmodule "uac_registrant.so"
# --- END TRUNK INTEGRATION WAVE 2: Module Loading ---

# --- Module parameters ---

# --- BEGIN TLS ROTATION WAVE 3 ---
# httpd binds to internal Docker networks only (port 8888 is NOT published in compose)
modparam("httpd", "ip", "${OPENSIPS_LISTEN_IP}")
modparam("httpd", "port", 8888)
# mi_http root path for MI commands
modparam("mi_http", "root", "mi")
# --- END TLS ROTATION WAVE 3 ---

# auth
modparam("auth", "nonce_expire", 30)
modparam("auth", "secret", "${AUTH_SECRET_32_CHARS}")

# auth_db
modparam("auth_db", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("auth_db", "calculate_ha1", 0)
modparam("auth_db", "use_domain", 1)
modparam("auth_db", "password_column", "ha1")
modparam("auth_db", "hash_column_sha256", "ha1_sha256")
modparam("auth_db", "hash_column_sha512t256", "ha1_sha512t256")
modparam("auth_db", "load_credentials", "$avp(tenant_id)=tenant_id;$avp(route_setid)=routing_group")

# sqlops
modparam("sqlops", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")

# dispatcher
modparam("dispatcher", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("dispatcher", "table_name", "dispatcher")
modparam("dispatcher", "ds_ping_method", "OPTIONS")
# Wave 5: ds_ping_interval=30 for trunk provider probes (setid 100)
# Per-destination attrs override: ping_interval=30 for PBX backends if needed.
modparam("dispatcher", "ds_ping_interval", 30)
modparam("dispatcher", "ds_probing_mode", 1)
modparam("dispatcher", "ds_probing_threshold", 3)

# T3.1: Load-based dispatcher routing
modparam("dispatcher", "ds_ping_from", "sip:healthcheck@localhost")
# Load-based weights: "f" flag in ds_select_dst uses priority/weight
# Target capacity threshold: 80% (checked in route)

# T3.2: Dispatcher load monitoring
# ds_probing_mode=1 probes all destinations when none are available.
# Manual target state query via MI:
#   opensips-cli -x mi ds_list
#   opensips-cli -x mi ds_set_state

# rtpengine
modparam("rtpengine", "rtpengine_sock", "udp:${RTPENGINE_HOST}:22222")
modparam("rtpengine", "rtpengine_tout", 2)
modparam("rtpengine", "rtpengine_retr", 3)
modparam("rtpengine", "rtpengine_disable_tout", 30)

# permissions
modparam("permissions", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("permissions", "address_table", "address")

# topology_hiding
modparam("topology_hiding", "force_dialog", 1)
modparam("topology_hiding", "th_callid_passwd", "${TOPOLOGY_SECRET}")
modparam("topology_hiding", "th_callid_prefix", "TSISIP_")

# --- BEGIN TLS ROTATION WAVE 3 ---
# tls_mgm - OpenSIPS 3.6 syntax: server_domain defines the domain name,
# then certificate/private_key/ca_list are separate modparams using [domain]/path syntax.
# Updated to use shared tls_certs volume for automated certificate rotation.
modparam("tls_mgm", "server_domain", "default")
modparam("tls_mgm", "certificate", "[default]/certs/live/server.crt")
modparam("tls_mgm", "private_key", "[default]/certs/live/server.key")
modparam("tls_mgm", "ca_list", "[default]/certs/live/ca.crt")
modparam("tls_mgm", "verify_cert", "[default]1")
modparam("tls_mgm", "require_cert", "[default]0")
modparam("tls_mgm", "ciphers_list", "[default]ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:!CBC:!aNULL:!MD5:!DSS")
modparam("tls_mgm", "tls_method", "[default]TLSv1_2")
# --- END TLS ROTATION WAVE 3 ---

# tm
modparam("tm", "fr_timeout", 5)
modparam("tm", "fr_inv_timeout", 60)

# acc (CDR logging) — OpenSIPS 3.6 uses do_accounting() in routing script
modparam("acc", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("dialplan", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("domain", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("acc", "db_table_acc", "cdr")

# rr
modparam("rr", "enable_double_rr", 1)

# maxfwd
modparam("maxfwd", "max_limit", 70)

# pike (IP throttling)
modparam("pike", "sampling_time_unit", 2)
modparam("pike", "reqs_density_per_unit", 50)
modparam("pike", "remove_latency", 10)

# ratelimit (auth throttling + global anomaly throttle)
modparam("ratelimit", "timer_interval", 5)
modparam("ratelimit", "expire_time", 3600)
modparam("ratelimit", "hash_size", 4096)
modparam("ratelimit", "default_algorithm", "TAILDROP")

# cachedb_local (T2.1 auth failures, T4.1 ban list, T5.3 anomaly state)
# In-memory key-value store with TTL — OpenSIPS 3.6 replacement for htable
modparam("cachedb_local", "cachedb_url", "local:///")
# T4.2 MI commands (via cachedb_local script functions):
#   Add ban:     cache_store("local", "ban_list_<ip>", "<reason>", "3600")
#   Delete ban:  cache_remove("local", "ban_list_<ip>")
#   Check ban:   cache_fetch("local", "ban_list_<ip>", $avp(result))

# userblacklist (ban list)
modparam("userblacklist", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("userblacklist", "db_table", "userblacklist")
modparam("userblacklist", "use_domain", 0)

# --- BEGIN TRUNK INTEGRATION WAVE 2: UAC Registrant Configuration ---
# uac_registrant: auto-REGISTER to trunk providers requiring registration
modparam("uac_registrant", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("uac_registrant", "table_name", "sip_trunk_registrations")
modparam("uac_registrant", "timer_interval", 60)
# --- END TRUNK INTEGRATION WAVE 2: UAC Registrant Configuration ---

# --- BEGIN TRUNK INTEGRATION WAVE 2: UAC Auth Configuration ---
# uac_auth: per-trunk digest authentication via AVPs
modparam("uac_auth", "auth_username_avp", "$avp(trunk_auth_user)")
modparam("uac_auth", "auth_password_avp", "$avp(trunk_auth_pass)")
modparam("uac_auth", "auth_realm_avp", "$avp(trunk_auth_realm)")
# --- END TRUNK INTEGRATION WAVE 2: UAC Auth Configuration ---

# --- BEGIN TRUNK INTEGRATION WAVE 2: Dispatcher Trunk Probe Documentation ---
# Trunk health probes use dispatcher setid 100.
# Per-destination ping_interval should be set via dispatcher table attrs column
# (e.g., attrs='ping_interval=30;ping_from=sip:healthcheck@tsisip') or MI.
# Global ds_probing_threshold=5 applies; setid-specific destinations are
# populated via SQL insert into dispatcher(setid=100) or MI ds_reload.
# --- END TRUNK INTEGRATION WAVE 2: Dispatcher Trunk Probe Documentation ---

# --- BEGIN TRUNK INTEGRATION WAVE 3: ACC CDR Enrichment ---
modparam("acc", "extra_fields", "db: trunk_provider_id; trunk_name; direction")
# --- END TRUNK INTEGRATION WAVE 3: ACC CDR Enrichment ---


# --- Request Route ---
route {
    # T1.2: Enterprise NAT whitelist (grp=2 in address table)
    # Known enterprise NAT IPs bypass pike to avoid false-positive blocks
    # on high-volume legitimate trunk traffic.
    if (check_source_address(2)) {
        xlog("L_INFO", "Enterprise NAT whitelist $si - bypassing pike\n");
    } else {
        # T1.1: Per-source IP throttling (pike)
        if (!pike_check_req()) {
            xlog("L_WARN", "PIKE blocked $si - rate limit exceeded\n");
            drop;
            exit;
        }
    }

    if (!has_totag()) {
        # Initial request
        if (is_method("OPTIONS")) {
            # Health-check OPTIONS - no auth, no backend routing
            sl_send_reply(200, "OK");
            exit;
        }
    } else {
        # In-dialog request
        if (loose_route()) {
            # T4.3: Handle re-INVITE SDP for SRTP hold/resume
            if (is_method("INVITE") && has_body("application/sdp")) {
                route(SRTP_REOFFER);
            }
            # T4.3: Clean up RTPengine session on BYE
            if (is_method("BYE")) {
                rtpengine_delete();
            }
            route(RELAY);
        } else {
            sl_send_reply(404, "Not Here");
        }
        exit;
    }

    # T4.1: Ban list check (initial requests only — in-dialog already handled)
    if (cache_fetch("local", "ban_list_$si", $avp(ban_reason))) {
        xlog("L_WARN", "BAN_LIST denied $si (reason=$avp(ban_reason))\n");
        sl_send_reply(403, "Forbidden");
        exit;
    }

    # T5.3: Global anomaly throttle with dynamic threshold
    # Normal baseline: 500 rps. When anomaly_state flag is set, reduce to 250 rps.
    if (cache_fetch("local", "anomaly_state_global_throttle", $avp(throttle_active))) {
        if (!rl_check("global_alert", 250, "TAILDROP")) {
            xlog("L_WARN", "Global anomaly throttle active - $si rate limited\n");
            drop;
            exit;
        }
    } else {
        if (!rl_check("global", 500, "TAILDROP")) {
            xlog("L_WARN", "Global throttle active - $si rate limited\n");
            drop;
            exit;
        }
    }

    # Max-Forwards / loop detection
    if (!mf_process_maxfwd_header(70)) {
        sl_send_reply(483, "Too Many Hops");
        exit;
    }

    # Message size limit (4096 bytes per RFC 3261 recommendation)
    if ($ml > 4096) {
        sl_send_reply(513, "Message Too Large");
        exit;
    }

    # CANCEL requests
    if (is_method("CANCEL")) {
        if (t_check_trans()) {
            t_relay();
        }
        exit;
    }

    # Sanitize untrusted headers
    route(SANITIZE);

    # T2.3: Verify mutual TLS for trunk connections
    route(TRUNK_VERIFY);

    # --- BEGIN TRUNK INTEGRATION WAVE 4: Inbound DID Routing ---
    # Inbound trunk-originated INVITEs bypass auth and route via DID mapping
    if (is_method("INVITE")) {
        route(INBOUND_DID_ROUTING);
    }
    # --- END TRUNK INTEGRATION WAVE 4: Inbound DID Routing ---

    # Check per-user blacklist entries after protocol-level health traffic
    if (!check_user_blacklist("$fU", "$fd", "$rU", "userblacklist")) {
        xlog("L_WARN", "USERBLACKLIST denied $fU@$fd -> $rU from $si\n");
        sl_send_reply(403, "Forbidden");
        exit;
    }

    # Trusted gateway bypass (permissions module)
    if (check_source_address(1)) {
        xlog("L_INFO", "Trusted gateway $si - bypassing auth\n");
        route(HEADER_ROUTING);
        route(RELAY);
        exit;
    }

    # Authentication
    route(AUTH);

    # Trunk routing for outbound calls to non-local domains
    route(TRUNK_ROUTING);

    # Header-based routing (local tenant domains)
    route(HEADER_ROUTING);

    # INVITE-specific handling: dialog + topology hiding + media
    if (is_method("INVITE")) {
        route(HANDLE_INVITE);
    }

    route(RELAY);
}

# --- Reply Route ---
onreply_route {
    # Handle negative replies for failure routing
    if ($rs =~ "^(408|500|502|503|504)$") {
        xlog("L_WARN", "Failure reply $rs from $si - triggering failover\n");
    }

    # T4.2/T4.3: Handle SDP answer for SRTP on 2xx replies to INVITE/re-INVITE
    if ($rs =~ "^2[0-9][0-9]$" && has_body("application/sdp")) {
        if (!rtpengine_answer()) {
            xlog("L_ERR", "RTPengine answer failed for $ci\n");
        }
    }
}

# --- Failure Route ---
failure_route[FAILOVER] {
    if (0) {
        exit;
    }

    # Dispatcher failover on failure
    if (t_check_status("408|500|502|503|504")) {
        if (ds_next_dst()) {
            t_on_failure("FAILOVER");
            route(RELAY);
            exit;
        }
    }

    xlog("L_ERR", "All dispatcher targets failed for $ru\n");
}

# --- Branch Route ---
branch_route[BRANCH_MANAGE] {
    xlog("L_INFO", "Branch to $du ($si:$sp)\n");
}

# --- Sub-routes ---

route[SANITIZE] {
    # Remove potentially dangerous headers from untrusted sources
    # Canonical spec §17: strip all untrusted inbound routing/identity headers
    remove_hf("X-TSiSIP-Internal");
    remove_hf("X-Backend-IP");
    remove_hf("P-Asserted-Identity");
    remove_hf("P-Preferred-Identity");
    remove_hf("X-Tenant-ID");
    remove_hf("X-Backend-ID");
    remove_hf("X-Route-Override");
    # Strip credentials before forwarding (prevent credential leakage)
    remove_hf("Authorization");
    remove_hf("Proxy-Authorization");
}

route[AUTH] {
    # SQL injection guard: validate auth username (RFC 3261: user-unreserved chars, max 128)
    if ($au != NULL && !($au =~ "^[a-zA-Z0-9_.!~*'()&=+$,;?/-]{1,128}$")) {
        xlog("L_WARN", "AUTH: rejected malformed username from $si\n");
        sl_send_reply(400, "Bad Request");
        exit;
    }

    # T2.1 / T2.2: htable-based auth failure throttling
    # Key: username if available, else source IP for unauthenticated probes
    $var(auth_key) = $au;
    if ($var(auth_key) == NULL) {
        $var(auth_key) = $si;
    }

    # After 3 failed auths within 60s, return 429 Too Many Requests
    if (cache_fetch("local", "auth_failures_$var(auth_key)", $avp(auth_fail_count)) && $(avp(auth_fail_count){s.int}) >= 3) {
        xlog("L_WARN", "Auth throttled for $var(auth_key) from $si - too many failures\n");
        sl_send_reply(429, "Too Many Requests");
        exit;
    }

    # Legacy ratelimit per user (10 attempts per 60s window) — kept for compatibility
    if ($au != NULL && !rl_check("auth_$au", 10, "TAILDROP")) {
        xlog("L_WARN", "Auth rate limited for $au ($si)\n");
        # Add to userblacklist via SQL (async cleanup by external job)
        sql_query("INSERT INTO userblacklist (username, domain, prefix, whitelist) VALUES ('$au', '$fd', 'auth_ban', 0) ON CONFLICT DO NOTHING");
        sl_send_reply(403, "Forbidden");
        exit;
    }

    # Auth contract: REGISTER uses www_authorize (401), non-REGISTER uses proxy_authorize (407)
    if (is_method("REGISTER")) {
        if (!www_authorize("$td", "subscriber")) {
            $var(audit_result) = "failure";
            route(AUTH_AUDIT);
            # T2.1: Increment auth failure counter (60s TTL)
            cache_add("local", "auth_failures_$var(auth_key)", 1, 60);
            # T2.2 / T4.1: Ban source IP after 3 auth failures
            if (cache_fetch("local", "auth_failures_$var(auth_key)", $avp(auth_fail_count)) && $(avp(auth_fail_count){s.int}) >= 3) {
                xlog("L_WARN", "Auth failure threshold reached for $var(auth_key) from $si - adding to ban_list\n");
                cache_store("local", "ban_list_$si", "auth_exceeded", 3600);
            }
            www_challenge("$td", "auth");
            exit;
        }
        # Audit log for REGISTER success
        $var(audit_result) = "success";
        route(AUTH_AUDIT);
        # T2.2: Reset auth failure counter on success
        cache_remove("local", "auth_failures_$var(auth_key)");
        consume_credentials();
        return;
    }

    # Non-REGISTER requests: proxy_authorize returns 407 Proxy Authentication Required
    if (!proxy_authorize("$fd", "subscriber")) {
        $var(audit_result) = "failure";
        route(AUTH_AUDIT);
        # T2.1: Increment auth failure counter (60s TTL)
        cache_add("local", "auth_failures_$var(auth_key)", 1, 60);
        # T2.2 / T4.1: Ban source IP after 3 auth failures
        if (cache_fetch("local", "auth_failures_$var(auth_key)", $avp(auth_fail_count)) && $(avp(auth_fail_count){s.int}) >= 3) {
            xlog("L_WARN", "Auth failure threshold reached for $var(auth_key) from $si - adding to ban_list\n");
            cache_store("local", "ban_list_$si", "auth_exceeded", 3600);
        }
        proxy_challenge("$fd", "auth");
        exit;
    }

    # Auth success - reset rate limit counter for this user
    rl_reset_count("auth_$au");
    # T2.2: Reset auth failure counter on success
    cache_remove("local", "auth_failures_$var(auth_key)");

    # Audit log for non-REGISTER success
    $var(audit_result) = "success";
    route(AUTH_AUDIT);

    consume_credentials();
}

route[AUTH_AUDIT] {
    # SQL injection guard: truncate Call-ID to 128 chars and validate charset
    # $ci (Call-ID) is attacker-controlled; $si (source IP) and $rm (method) are proxy-internal
    $var(safe_callid) = $ci;
    if ($var(safe_callid) == NULL || !($var(safe_callid) =~ "^[a-zA-Z0-9_.@:;=+*%!~()-]{1,128}$")) {
        $var(safe_callid) = "INVALID";
    }
    # Insert auth audit record
    sql_query("INSERT INTO auth_audit_log (event_time, username, domain, source_ip, sip_method, result, call_id) VALUES (NOW(), '$fU', '$fd', '$si', '$rm', '$var(audit_result)', '$var(safe_callid)')");
}

route[HEADER_ROUTING] {
    # Feature 002: Multi-Tenant Header Routing
    # Priority: header_routing_rules -> subscriber routing_group -> default set 1

    $var(ds_set) = 0;
    $var(tenant_id) = $avp(tenant_id);

    # 1. Try header_routing_rules match on X-Route-Key
    if ($hdr(X-Route-Key) != NULL && $hdr(X-Route-Key) != "") {
        # SQL injection guard: validate X-Route-Key (alphanumeric + ._- only, max 64 chars)
        $var(route_key) = $hdr(X-Route-Key);
        if (!($var(route_key) =~ "^[a-zA-Z0-9._-]{1,64}$")) {
            xlog("L_WARN", "HEADER_ROUTING: rejected invalid X-Route-Key from $si\n");
            sl_send_reply(400, "Bad Request");
            exit;
        }
        $avp(ra) = 0;
        sql_query("SELECT dispatcher_setid FROM header_routing_rules WHERE tenant_id = '$var(tenant_id)' AND header_name = 'X-Route-Key' AND match_value = '$var(route_key)' AND enabled = true ORDER BY priority LIMIT 1", "$avp(ra)");
        if ($rc == -1) {
            xlog("L_ERR", "HEADER_ROUTING: database error for tenant $var(tenant_id)\n");
            sl_send_reply(480, "Temporarily Unavailable");
            exit;
        }
        if ($avp(ra) != 0) {
            $var(ds_set) = $avp(ra);
            xlog("L_INFO", "HEADER_ROUTING: matched X-Route-Key=$hdr(X-Route-Key) -> set $var(ds_set) for tenant $var(tenant_id)\n");
        }
        $avp(ra) = NULL;
    }

    # 2. Fall back to subscriber routing_group
    if ($var(ds_set) == 0) {
        $var(ds_set) = $avp(route_setid);
        if ($var(ds_set) == 0) {
            $var(ds_set) = 1;
        }
        xlog("L_INFO", "HEADER_ROUTING: fallback to subscriber routing_group -> set $var(ds_set) for tenant $var(tenant_id)\n");
    }

    # 3. Sanitize routing headers before relay to backend
    remove_hf("X-Route-Key");
    remove_hf("X-Routing-Key");

    # 4. Load-based selection with capacity check
    if (!ds_select_dst($var(ds_set), 4, "f")) {
        sl_send_reply(480, "Temporarily Unavailable");
        exit;
    }

    xlog("L_INFO", "Selected dispatcher set $var(ds_set) for $ru\n");
}

route[HANDLE_INVITE] {
    # Create dialog
    if (!create_dialog("B")) {
        sl_send_reply(500, "Internal Server Error");
        exit;
    }

    # Enable CDR accounting for this call (OpenSIPS 3.6 API)
    do_accounting("db", "cdr");

    # Topology hiding
    topology_hiding("C");

    # T4.2: Media relay offer with SRTP for TLS-signaled calls
    if ($socket_in(proto) == "TLS") {
        # SRTP via SDES for TLS connections
        if (!rtpengine_offer("RTP/SAVP replace-origin replace-session-connection")) {
            sl_send_reply(488, "Not Acceptable Here");
            exit;
        }
    } else {
        # Plain RTP for non-TLS connections
        if (!rtpengine_offer("replace-origin replace-session-connection")) {
            sl_send_reply(488, "Not Acceptable Here");
            exit;
        }
    }
}

route[SRTP_REOFFER] {
    # T4.3: Re-INVITE with SDP (hold/resume) - renegotiate SRTP context
    if ($socket_in(proto) == "TLS") {
        if (!rtpengine_offer("RTP/SAVP replace-origin replace-session-connection")) {
            xlog("L_WARN", "SRTP re-offer failed for $ci\n");
            sl_send_reply(488, "Not Acceptable Here");
            exit;
        }
    } else {
        if (!rtpengine_offer("replace-origin replace-session-connection")) {
            xlog("L_WARN", "RTP re-offer failed for $ci\n");
            sl_send_reply(488, "Not Acceptable Here");
            exit;
        }
    }
}

route[TRUNK_VERIFY] {
    # T2.3: Mutual TLS for trunks
    # Check if source IP is a registered trunk requiring mTLS
    $avp(trunk_check) = 0;
    sql_query("SELECT 1 FROM trunk_ips WHERE ip_address = '$si' AND enabled = true AND require_mtls = true LIMIT 1", "$avp(trunk_check)");
    if ($rc == -1) {
        xlog("L_ERR", "TRUNK_VERIFY: database error for trunk lookup\n");
        sl_send_reply(480, "Temporarily Unavailable");
        exit;
    }

    if ($avp(trunk_check) != 0) {
        # Source is a trunk - require TLS and client certificate
        if ($socket_in(proto) != "TLS") {
            xlog("L_WARN", "TRUNK_VERIFY: trunk $si rejected - not using TLS\n");
            sl_send_reply(403, "Forbidden - TLS Required");
            exit;
        }
        if ($tls_peer_subject_cn == NULL || $tls_peer_subject_cn == "") {
            xlog("L_WARN", "TRUNK_VERIFY: trunk $si rejected - no client certificate\n");
            sl_send_reply(403, "Forbidden - Client Certificate Required");
            exit;
        }
        xlog("L_INFO", "TRUNK_VERIFY: trunk $si authenticated via mTLS CN=$tls_peer_subject_cn\n");
    }
    $avp(trunk_check) = NULL;
}

route[TRUNK_ROUTING] {
    # --- BEGIN TRUNK INTEGRATION WAVE 3: Outbound Trunk Routing ---
    # Only process INVITEs for trunk routing
    if (!is_method("INVITE")) {
        return;
    }

    # Check if destination is a local tenant domain
    $var(is_local_domain) = 0;
    sql_query_one("SELECT 1 FROM tenants WHERE sip_domain = '$rd' AND enabled = true LIMIT 1", "$var(is_local_domain)");
    if ($rc == -1) {
        xlog("L_ERR", "TRUNK_ROUTING: DB error during local domain check for $rd\n");
        sl_send_reply(480, "Temporarily Unavailable");
        exit;
    }

    if ($var(is_local_domain) != 0) {
        # Local domain; continue to HEADER_ROUTING -> Asterisk
        return;
    }

    # External destination: select highest-priority enabled and healthy trunk
    $var(trunk_found) = 0;
    $var(trunk_priority) = -1;
    $var(attempt) = 0;
    while ($var(attempt) < 5 && $var(trunk_found) == 0) {
        $var(attempt) = $var(attempt) + 1;

        $var(trunk_query) = "SELECT id, name, host, port, transport, auth_username, from_domain, caller_id_prefix, srtp_mode, max_cps, priority FROM sip_trunk_providers WHERE enabled = true AND priority > " + $var(trunk_priority) + " ORDER BY priority ASC LIMIT 1";
        $var(trunk_id) = 0;
        sql_query_one($var(trunk_query), "$var(trunk_id);$var(trunk_name);$var(trunk_host);$var(trunk_port);$var(trunk_transport);$var(trunk_auth_user);$var(trunk_from_domain);$var(trunk_cid_prefix);$var(trunk_srtp);$var(trunk_cps);$var(trunk_priority)");
        if ($rc == -1) {
            xlog("L_ERR", "TRUNK_ROUTING: DB error during trunk selection\n");
            sl_send_reply(503, "No Trunk Available");
            exit;
        }

        if ($var(trunk_id) == NULL || $var(trunk_id) == 0) {
            break;
        }

        # Wave 5: Skip unhealthy trunks based on dispatcher probe state
        if (cache_fetch("local", "trunk_health_$var(trunk_id)", $var(trunk_health))) {
            if ($var(trunk_health) == "unhealthy") {
                xlog("L_INFO", "TRUNK_ROUTING: skipping unhealthy trunk $var(trunk_name) (id=$var(trunk_id))\n");
            } else {
                $var(trunk_found) = 1;
            }
        } else {
            $var(trunk_found) = 1;
        }
    }

    if ($var(trunk_found) == 0) {
        xlog("L_WARN", "TRUNK_ROUTING: no healthy trunk provider available for $ru\n");
        sl_send_reply(503, "No Trunk Available");
        exit;
    }

    # Per-trunk CPS rate limiting (T3.3)
    if (!rl_check("trunk_$var(trunk_id)", $var(trunk_cps), "TAILDROP")) {
        xlog("L_WARN", "TRUNK_ROUTING: trunk $var(trunk_name) CPS exceeded (limit=$var(trunk_cps))\n");
        sl_send_reply(503, "Trunk Capacity Exceeded");
        exit;
    }

    # Mark direction and trunk metadata for CDR (T3.5)
    $avp(direction) = "outbound";
    $avp(trunk_provider_id) = $var(trunk_id);

    # Create dialog for trunk call
    if (!create_dialog("B")) {
        sl_send_reply(500, "Internal Server Error");
        exit;
    }

    # Enable CDR accounting
    do_accounting("db", "cdr");

    # Topology hiding
    topology_hiding("C");

    # Rewrite R-URI to trunk provider destination
    $ru = "sip:" + $rU + "@" + $var(trunk_host) + ":" + $var(trunk_port);
    if ($var(trunk_transport) == "tls") {
        $ru = $ru + ";transport=tls";
    } else if ($var(trunk_transport) == "tcp") {
        $ru = $ru + ";transport=tcp";
    }

    # Apply caller ID prefix if configured
    if ($var(trunk_cid_prefix) != NULL && $var(trunk_cid_prefix) != "") {
        uac_replace_from("$var(trunk_cid_prefix)$fU", "sip:$var(trunk_cid_prefix)$fU@$fd");
    }

    # Override From domain if configured
    if ($var(trunk_from_domain) != NULL && $var(trunk_from_domain) != "") {
        uac_replace_from("$fU", "sip:$fU@$var(trunk_from_domain)");
    }

    # Set auth realm for potential UAC auth challenge
    if ($var(trunk_auth_user) != NULL && $var(trunk_auth_user) != "") {
        $avp(trunk_auth_realm) = $var(trunk_host);
        # NOTE: $avp(trunk_auth_pass) must be populated by runtime credential resolver
        # as auth_password_encrypted is pgcrypto-encrypted and not directly usable.
    }

    # Set branch route for SRTP handling (T3.4)
    t_on_branch("BRANCH_TRUNK_SRTP");

    # Set failure route for trunk failover (T3.2)
    t_on_failure("TRUNK_FAILOVER");

    xlog("L_INFO", "TRUNK_ROUTING: routing to trunk $var(trunk_name) ($var(trunk_host):$var(trunk_port)) for $ru\n");

    # Add Record-Route for dialog path
    record_route();

    # Relay statefully
    if (!t_relay()) {
        sl_reply_error();
    }
    exit;
    # --- END TRUNK INTEGRATION WAVE 3: Outbound Trunk Routing ---
}

failure_route[TRUNK_FAILOVER] {
    # --- BEGIN TRUNK INTEGRATION WAVE 3: Trunk Failover ---
    # Handle trunk auth challenge (401|407)
    if (t_check_status("401|407")) {
        if ($var(trunk_auth_user) != NULL && $var(trunk_auth_user) != "") {
            if (uac_auth("MD5,SHA-256")) {
                t_on_failure("TRUNK_FAILOVER");
                t_on_branch("BRANCH_TRUNK_SRTP");
                record_route();
                if (!t_relay()) {
                    t_reply(500, "Server Error");
                }
                exit;
            }
            xlog("L_ERR", "TRUNK_FAILOVER: uac_auth failed for trunk $var(trunk_name)\n");
        }
    }

    # On transport/failure, try next priority healthy trunk
    if (t_check_status("408|480|500|502|503|504")) {
        $var(failover_found) = 0;
        $var(failover_attempt) = 0;
        while ($var(failover_attempt) < 5 && $var(failover_found) == 0) {
            $var(failover_attempt) = $var(failover_attempt) + 1;
            $var(trunk_query) = "SELECT id, name, host, port, transport, auth_username, from_domain, caller_id_prefix, srtp_mode, max_cps, priority FROM sip_trunk_providers WHERE enabled = true AND priority > '$var(trunk_priority)' ORDER BY priority ASC LIMIT 1";
            $var(trunk_id) = 0;
            sql_query_one($var(trunk_query), "$var(trunk_id);$var(trunk_name);$var(trunk_host);$var(trunk_port);$var(trunk_transport);$var(trunk_auth_user);$var(trunk_from_domain);$var(trunk_cid_prefix);$var(trunk_srtp);$var(trunk_cps);$var(trunk_priority)");
            if ($rc == -1) {
                xlog("L_ERR", "TRUNK_FAILOVER: DB error during failover selection\n");
                break;
            }

            if ($var(trunk_id) == NULL || $var(trunk_id) == 0) {
                break;
            }

            # Wave 5: Skip unhealthy trunks during failover
            if (cache_fetch("local", "trunk_health_$var(trunk_id)", $var(trunk_health))) {
                if ($var(trunk_health) == "unhealthy") {
                    xlog("L_INFO", "TRUNK_FAILOVER: skipping unhealthy trunk $var(trunk_name) (id=$var(trunk_id))\n");
                } else {
                    $var(failover_found) = 1;
                }
            } else {
                $var(failover_found) = 1;
            }
        }

        if ($var(failover_found) == 1) {
            # Update R-URI to next healthy trunk
            $ru = "sip:" + $rU + "@" + $var(trunk_host) + ":" + $var(trunk_port);
            if ($var(trunk_transport) == "tls") {
                $ru = $ru + ";transport=tls";
            } else if ($var(trunk_transport) == "tcp") {
                $ru = $ru + ";transport=tcp";
            }

            # Apply caller ID prefix if configured
            if ($var(trunk_cid_prefix) != NULL && $var(trunk_cid_prefix) != "") {
                uac_replace_from("$var(trunk_cid_prefix)$fU", "sip:$var(trunk_cid_prefix)$fU@$fd");
            }

            # Override From domain if configured
            if ($var(trunk_from_domain) != NULL && $var(trunk_from_domain) != "") {
                uac_replace_from("$fU", "sip:$fU@$var(trunk_from_domain)");
            }

            # Set auth realm for potential UAC auth challenge
            if ($var(trunk_auth_user) != NULL && $var(trunk_auth_user) != "") {
                $avp(trunk_auth_realm) = $var(trunk_host);
            }

            t_on_branch("BRANCH_TRUNK_SRTP");
            t_on_failure("TRUNK_FAILOVER");

            xlog("L_INFO", "TRUNK_FAILOVER: failing over to trunk $var(trunk_name) ($var(trunk_host):$var(trunk_port))\n");

            record_route();
            if (!t_relay()) {
                t_reply(500, "Server Error");
            }
            exit;
        }
    }

    xlog("L_ERR", "TRUNK_FAILOVER: all trunk targets failed for $ru\n");
    # --- END TRUNK INTEGRATION WAVE 3: Trunk Failover ---
}

branch_route[BRANCH_TRUNK_SRTP] {
    # --- BEGIN TRUNK INTEGRATION WAVE 3: Trunk SRTP Branch Route ---
    xlog("L_INFO", "BRANCH_TRUNK_SRTP: branch to trunk $var(trunk_name) ($du)\n");

    if ($var(trunk_srtp) == "sdes") {
        if (!rtpengine_offer("RTP/SAVP replace-origin replace-session-connection")) {
            xlog("L_ERR", "BRANCH_TRUNK_SRTP: SRTP offer failed for $ci\n");
        }
    } else if ($var(trunk_srtp) == "dtls") {
        if (!rtpengine_offer("UDP/TLS/RTP/SAVP replace-origin replace-session-connection")) {
            xlog("L_ERR", "BRANCH_TRUNK_SRTP: DTLS-SRTP offer failed for $ci\n");
        }
    } else {
        if (!rtpengine_offer("replace-origin replace-session-connection")) {
            xlog("L_ERR", "BRANCH_TRUNK_SRTP: RTP offer failed for $ci\n");
        }
    }
    # --- END TRUNK INTEGRATION WAVE 3: Trunk SRTP Branch Route ---
}

route[INBOUND_DID_ROUTING] {
    # --- BEGIN TRUNK INTEGRATION WAVE 4: Inbound DID Routing ---
    # Only process INVITEs from known trunk IPs (check against sip_trunk_providers.host)
    $var(trunk_provider_id) = 0;
    sql_query_one("SELECT id FROM sip_trunk_providers WHERE host = '$si' AND enabled = true LIMIT 1", "$var(trunk_provider_id)");
    if ($rc == -1) {
        xlog("L_ERR", "INBOUND_DID_ROUTING: DB error during trunk lookup for $si\n");
        sl_send_reply(480, "Temporarily Unavailable");
        exit;
    }

    if ($var(trunk_provider_id) == NULL || $var(trunk_provider_id) == 0) {
        # Not a trunk source; fall through to normal auth flow
        return;
    }

    xlog("L_INFO", "INBOUND_DID_ROUTING: trunk-originated INVITE from $si (provider=$var(trunk_provider_id))\n");

    # Query DID mapping for the called number (RURI user part)
    $var(did_setid) = 0;
    sql_query_one("SELECT tenant_id, dispatcher_setid FROM sip_trunk_did_mappings WHERE did_number = '$rU' AND enabled = true LIMIT 1", "$var(tenant_id);$var(did_setid)");
    if ($rc == -1) {
        xlog("L_ERR", "INBOUND_DID_ROUTING: DB error during DID lookup for $rU\n");
        sl_send_reply(480, "Temporarily Unavailable");
        exit;
    }

    if ($var(did_setid) == NULL || $var(did_setid) == 0) {
        xlog("L_WARN", "INBOUND_DID_ROUTING: no DID mapping for $rU from trunk $si\n");
        sl_send_reply(404, "DID Not Found");
        exit;
    }

    # Mark direction and trunk metadata for CDR
    $avp(direction) = "inbound";
    $avp(trunk_provider_id) = $var(trunk_provider_id);

    # Preserve tenant ID for downstream Asterisk routing
    append_hf("X-Tenant-ID: $var(tenant_id)\r\n");

    # Select backend from dispatcher set
    if (!ds_select_dst($var(did_setid), 4, "f")) {
        xlog("L_WARN", "INBOUND_DID_ROUTING: no backend available in set $var(did_setid) for DID $rU\n");
        sl_send_reply(503, "No Backend Available");
        exit;
    }

    # Apply topology hiding and media handling (HANDLE_INVITE applies topology_hiding + rtpengine_offer)
    route(HANDLE_INVITE);

    # Relay to selected backend
    route(RELAY);
    exit;
    # --- END TRUNK INTEGRATION WAVE 4: Inbound DID Routing ---
}

route[RELAY] {
    # Add Record-Route for dialog path
    if (is_method("INVITE|SUBSCRIBE|REFER")) {
        record_route();
    }

    # Set branch and failure handlers
    t_on_branch("BRANCH_MANAGE");
    t_on_failure("FAILOVER");

    # Relay statefully
    if (!t_relay()) {
        sl_reply_error();
    }
    exit;
}

# --- Event Routes (Feature 006) ---

event_route[E_PIKE_BLOCKED] {
    xlog("L_WARN", "E_PIKE_BLOCKED: source=$param(src_ip) limit=$param(limit)\n");
    # T4.1: Auto-ban pike-blocked sources for extended protection (1h TTL)
    cache_store("local", "ban_list_$param(src_ip)", "pike_blocked", 3600);
}

event_route[E_AUTH_FAILURE] {
    xlog("L_WARN", "E_AUTH_FAILURE: user=$param(credentials) src=$si\n");
}

event_route[E_DISPATCHER_STATUS] {
    xlog("L_INFO", "E_DISPATCHER_STATUS: uri=$param(uri) status=$param(status)\n");

    # --- BEGIN TRUNK INTEGRATION WAVE 5: Trunk Health Monitoring ---
    # Track trunk provider health from dispatcher OPTIONS probes (setid 100)
    if ($param(uri) =~ "^sip:") {
        $var(trunk_uri) = $param(uri);
        $var(trunk_host) = $(var(trunk_uri){uri.host});
        $var(trunk_provider_id) = 0;
        sql_query_one("SELECT id FROM sip_trunk_providers WHERE host = '$var(trunk_host)' AND enabled = true LIMIT 1", "$var(trunk_provider_id)");
        if ($rc == -1) {
            xlog("L_ERR", "E_DISPATCHER_STATUS: failed to map $param(uri) to trunk provider\n");
        } else {
            if ($var(trunk_provider_id) != NULL && $var(trunk_provider_id) != 0) {
                if ($param(status) == "0") {
                    cache_store("local", "trunk_health_$var(trunk_provider_id)", "healthy", 3600);
                    xlog("L_INFO", "E_DISPATCHER_STATUS: trunk $var(trunk_provider_id) marked healthy\n");
                } else if ($param(status) == "1") {
                    cache_store("local", "trunk_health_$var(trunk_provider_id)", "unhealthy", 3600);
                    xlog("L_WARN", "E_DISPATCHER_STATUS: trunk $var(trunk_provider_id) marked unhealthy\n");
                }
            }
        }
    }
    # --- END TRUNK INTEGRATION WAVE 5: Trunk Health Monitoring ---
}
