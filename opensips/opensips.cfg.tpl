# TSiSIP OpenSIPS 3.6 LTS Edge Proxy Configuration
# Generated from template at container startup

# --- Memory ---
shm_mem_size=512
pkg_mem_size=16
memdump=1
memlog=30

# --- Network listeners ---
socket=udp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060
socket=tcp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060
# TLS socket - habilitado (certificados gerados via ca-tool)
socket=tls:${OPENSIPS_LISTEN_IP}:5061 as ${HOST_PUBLIC_IP}:5061

# --- Database ---
db_default_url="postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}"

mpath="/usr/local/lib64/opensips/modules/"

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
# TLS modules (carregados mesmo que socket TLS esteja comentado)
loadmodule "tls_mgm.so"
loadmodule "tls_openssl.so"
loadmodule "proto_udp.so"
loadmodule "proto_tcp.so"
loadmodule "proto_tls.so"

# --- Module parameters ---

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
modparam("dispatcher", "ds_ping_interval", 10)
modparam("dispatcher", "ds_probing_mode", 1)
modparam("dispatcher", "ds_probing_threshold", 5)
modparam("dispatcher", "persistent_state", 1)

# T3.1: Load-based dispatcher routing
modparam("dispatcher", "ds_ping_from", "sip:healthcheck@localhost")
# Load-based weights: "f" flag in ds_select_dst uses priority/weight
# Target capacity threshold: 80% (checked in route)

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

# tls_mgm - OpenSIPS 3.6 syntax: server_domain defines the domain name,
# then certificate/private_key/ca_list are separate modparams using [domain]/path syntax.
modparam("tls_mgm", "server_domain", "default")
modparam("tls_mgm", "certificate", "[default]/etc/opensips/tls/server.crt")
modparam("tls_mgm", "private_key", "[default]/etc/opensips/tls/server.key")
modparam("tls_mgm", "ca_list", "[default]/etc/opensips/tls/ca.crt")
modparam("tls_mgm", "verify_cert", "[default]1")
modparam("tls_mgm", "require_cert", "[default]0")

# tm
modparam("tm", "fr_timeout", 5)
modparam("tm", "fr_inv_timeout", 60)

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

# userblacklist (ban list)
modparam("userblacklist", "db_url", "postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}")
modparam("userblacklist", "db_table", "userblacklist")
modparam("userblacklist", "use_domain", 0)


# --- Request Route ---
route {
    # Per-source IP throttling (pike)
    if (!pike_check_req()) {
        xlog("L_WARN", "PIKE blocked $si - rate limit exceeded\n");
        drop;
        exit;
    }

    # Check userblacklist (manual bans)
    if (check_blacklist("userblacklist")) {
        xlog("L_WARN", "BLACKLISTED source $si - request dropped\n");
        sl_send_reply("403", "Forbidden");
        exit;
    }

    # Global anomaly throttle
    if (!rl_check("global", 500, "TAILDROP")) {
        xlog("L_WARN", "Global throttle active - $si rate limited\n");
        drop;
        exit;
    }

    # Max-Forwards / loop detection
    if (!mf_process_maxfwd_header("70")) {
        sl_send_reply("483", "Too Many Hops");
        exit;
    }

    # Message size limit (4096 bytes per RFC 3261 recommendation)
    if ($ml > 4096) {
        sl_send_reply("513", "Message Too Large");
        exit;
    }

    # CANCEL and in-dialog requests
    if (is_method("CANCEL")) {
        if (t_check_trans()) {
            t_relay();
        }
        exit;
    }

    if (!has_totag()) {
        # Initial request
        if (is_method("OPTIONS")) {
            # Health-check OPTIONS - no auth, no backend routing
            sl_send_reply("200", "OK");
            exit;
        }
    } else {
        # In-dialog request
        if (loose_route()) {
            route(RELAY);
        } else {
            sl_send_reply("404", "Not Here");
        }
        exit;
    }

    # Sanitize untrusted headers
    route(SANITIZE);

    # Trusted gateway bypass (permissions module)
    if (check_source_address("1")) {
        xlog("L_INFO", "Trusted gateway $si - bypassing auth\n");
        route(HEADER_ROUTING);
        route(RELAY);
        exit;
    }

    # Authentication
    route(AUTH);

    # Header-based routing
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
    if (t_check_status("408|500|502|503|504")) {
        xlog("L_WARN", "Failure reply $rs from $si - triggering failover\n");
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
    remove_hf("X-TSiSIP-Internal");
    remove_hf("X-Backend-IP");
}

route[AUTH] {
    # Auth rate limiting per user (10 attempts per 60s window)
    if (!rl_check("auth_$au", 10, "TAILDROP")) {
        xlog("L_WARN", "Auth rate limited for $au ($si)\n");
        # Add to userblacklist via SQL (async cleanup by external job)
        sql_query("db_default", "INSERT INTO userblacklist (username, domain, prefix, allowlist) VALUES ('$au', '$fd', 'auth_ban', 0) ON CONFLICT DO NOTHING");
        sl_send_reply("403", "Forbidden");
        exit;
    }

    if (!www_authorize("", "subscriber")) {
        www_challenge("", "0");
        exit;
    }

    # Auth success - reset rate limit counter for this user
    rl_reset_count("auth_$au");

    # Audit log
    $var(audit_result) = "success";
    route(AUTH_AUDIT);
}

route[AUTH_AUDIT] {
    # Insert auth audit record
    sql_query("db_default", "INSERT INTO auth_audit_log (event_time, username, domain, source_ip, sip_method, result, call_id) VALUES (NOW(), '$fU', '$fd', '$si', '$rm', '$var(audit_result)', '$ci')");
}

route[HEADER_ROUTING] {
    # Feature 002: Multi-Tenant Header Routing
    # Priority: header_routing_rules -> subscriber routing_group -> default set 1

    $var(ds_set) = 0;
    $var(tenant_id) = $avp(tenant_id);

    # 1. Try header_routing_rules match on X-Route-Key
    if ($hdr(X-Route-Key) != "") {
        sql_query("db_default", "SELECT dispatcher_setid FROM header_routing_rules WHERE tenant_id = '$var(tenant_id)' AND header_name = 'X-Route-Key' AND match_value = '$hdr(X-Route-Key)' AND enabled = true ORDER BY priority LIMIT 1", "ra");
        if ($avp(ra) != 0) {
            $var(ds_set) = $avp(ra);
            xlog("L_INFO", "HEADER_ROUTING: matched X-Route-Key=$hdr(X-Route-Key) -> set $var(ds_set) for tenant $var(tenant_id)\n");
        }
        avp_delete("$avp(ra)");
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
    if (!ds_select_dst("$var(ds_set)", "f")) {
        sl_send_reply("480", "Temporarily Unavailable");
        exit;
    }

    xlog("L_INFO", "Selected dispatcher set $var(ds_set) for $ru\n");
}

route[HANDLE_INVITE] {
    # Create dialog
    if (!create_dialog("B")) {
        sl_send_reply("500", "Internal Server Error");
        exit;
    }

    # Topology hiding
    topology_hiding("U");

    # Media relay offer
    rtpengine_offer("replace-origin replace-session-connection");
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
}

event_route[E_AUTH_FAILURE] {
    xlog("L_WARN", "E_AUTH_FAILURE: user=$param(credentials) src=$si\n");
}

event_route[E_DISPATCHER_STATUS] {
    xlog("L_INFO", "E_DISPATCHER_STATUS: uri=$param(uri) status=$param(status)\n");
}
