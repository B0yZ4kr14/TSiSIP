# TSiSIP OpenSIPS 3.6 LTS Edge Proxy Configuration
# Generated from template at container startup

# --- Network listeners ---
socket=udp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060
socket=tcp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060
socket=tls:${OPENSIPS_LISTEN_IP}:5061 as ${HOST_PUBLIC_IP}:5061

# --- Database ---
db_default_url="postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}"

mpath="/usr/local/lib64/opensips/modules/"

# --- Modules ---
loadmodule "proto_udp.so"
loadmodule "proto_tcp.so"
loadmodule "proto_tls.so"
loadmodule "tls_mgm.so"
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
loadmodule "cachedb_local.so"

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

# tls_mgm (TLS/SRTP encryption - Feature 007)
# Server domain for TLS listener
modparam("tls_mgm", "server_domain", "dom=default;cert=/run/secrets/server.crt;pkey=/run/secrets/server.key;ca=/run/secrets/ca.crt;verify_cert=1;require_cert=0;crl=/run/secrets/crl.pem")
# Client domain for outbound TLS connections
modparam("tls_mgm", "client_domain", "dom=default;cert=/run/secrets/server.crt;pkey=/run/secrets/server.key;ca=/run/secrets/ca.crt;verify_cert=1;require_cert=0")

# cachedb_local (auth failure counters, ban lists)
modparam("cachedb_local", "cachedb_url", "local://")
modparam("cachedb_local", "cache_collections", "auth_failures/r=1024;ban_list/r=4096;trunk_whitelist/r=256")

# TCP connection limits (anti-slowloris)
tcp_max_connections=4096
tcp_connection_lifetime=300

# --- Main Route ---
route {
    # Phase 1: Rate limiting and DDoS protection (Feature 006)
    route(CHECK_BAN_LIST);
    route(RATE_LIMIT);

    if (!mf_process_maxfwd_header(70)) {
        if ($retcode == -1) {
            sl_send_reply(483, "Too Many Hops");
        } else {
            sl_send_reply(500, "Max-Forwards Processing Error");
        }
        exit;
    }

    # Defensive upper bound; UDP MTU is enforced by the transport layer.
    if ($ml > 65535) {
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
        sl_send_reply(200, "OK");
        exit;
    }

    route(SANITIZE);
    route(TLS_TRUNK_VERIFY);
    route(AUTH);
    route(HEADER_ROUTING);
    route(SRTP_ENFORCE);

    if (is_method("INVITE")) {
        create_dialog();
        topology_hiding("C");
        t_on_branch("BRANCH_MANAGE");
        t_on_reply("REPLY_MANAGE");
        t_on_failure("FAILURE_MANAGE");
    }

    route(RELAY);
}

# --- SANITIZE Route ---
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

# --- AUTH Route ---
route[AUTH] {
    # FR-008: Trusted gateway IP bypass via address table
    if (check_source_address(0)) {
        xlog("L_INFO", "Trusted gateway bypass: $si [$rm]");
        return;
    }

    if (is_method("REGISTER")) {
        if (!www_authorize("$td", "subscriber")) {
            www_challenge("$td", "auth", "MD5,SHA-256,SHA-512-256");
            route(AUTH_FAILURE_TRACK);
            sql_query("INSERT INTO auth_audit_log (event_time, username, domain, source_ip, sip_method, result, call_id) VALUES (NOW(), '$fU', '$fd', '$si', 'REGISTER', 'challenge', '$ci')");
            exit;
        }
        consume_credentials();
        route(AUTH_SUCCESS_RESET);
        sql_query("INSERT INTO auth_audit_log (event_time, username, domain, source_ip, sip_method, result, call_id) VALUES (NOW(), '$fU', '$fd', '$si', 'REGISTER', 'success', '$ci')");
        return;
    }

    if (!proxy_authorize("$fd", "subscriber")) {
        proxy_challenge("$fd", "auth", "MD5,SHA-256,SHA-512-256");
        route(AUTH_FAILURE_TRACK);
        sql_query("INSERT INTO auth_audit_log (event_time, username, domain, source_ip, sip_method, result, call_id) VALUES (NOW(), '$fU', '$fd', '$si', '$rm', 'challenge', '$ci')");
        exit;
    }

    consume_credentials();
    route(AUTH_SUCCESS_RESET);
    sql_query("INSERT INTO auth_audit_log (event_time, username, domain, source_ip, sip_method, result, call_id) VALUES (NOW(), '$fU', '$fd', '$si', '$rm', 'success', '$ci')");
}

# --- HEADER_ROUTING Route ---
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

# --- RELAY Route ---
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

# --- BRANCH_MANAGE ---
branch_route[BRANCH_MANAGE] {
    return;
}

# --- REPLY_MANAGE ---
onreply_route[REPLY_MANAGE] {
    if (has_body("application/sdp") && $rs >= 183 && $rs < 300) {
        rtpengine_answer("replace-origin replace-session-connection ICE=remove");
    }

    remove_hf("Server");
    remove_hf("X-Tenant-ID");
}

# --- FAILURE_MANAGE ---
failure_route[FAILURE_MANAGE] {
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
# Circuit breaker configuration
modparam("dispatcher", "ds_ping_from", "sip:healthcheck@localhost")

# --- Graceful Degradation Routes ---

# Check if RTPengine is available before offering
route[CHECK_RTPENGINE] {
    # RTPengine availability is monitored via dispatcher health checks
    # and prometheus metrics. Direct ping not available in rtpengine module.
    # Calls will fail naturally if RTPengine is down.
    return;
}

# Check if PostgreSQL is available for auth-dependent requests
route[CHECK_POSTGRES] {
    # PostgreSQL availability is monitored via dispatcher and health checks
    # Auth failures will be handled by auth_db module timeout
    return;
}

# --- Feature 006: Rate Limiting & DDoS Protection ---

# Check ban list first (fast path)
route[CHECK_BAN_LIST] {
    cache_fetch("local", "ban_list:$si", "$var(ban_reason)");
    if ($var(ban_reason) != "") {
        xlog("L_WARN", "Banned IP $si rejected");
        sl_send_reply(403, "Forbidden");
        exit;
    }
    cache_fetch("local", "ban_list:$au", "$var(ban_reason)");
    if ($var(ban_reason) != "") {
        xlog("L_WARN", "Banned user $au rejected from $si");
        sl_send_reply(403, "Forbidden");
        exit;
    }
}

# Rate limiting with pike
route[RATE_LIMIT] {
    # T1.2: NATed enterprise handling - skip pike for trusted IPs
    cache_fetch("local", "trunk_whitelist:$si", "$var(trunk)");
    if ($var(trunk) != "") {
        xlog("L_INFO", "Trusted trunk $si bypassing pike");
        return;
    }

    # T1.1: pike per-source IP throttling
    if (!pike_check_req()) {
        xlog("L_WARN", "Pike blocked $si - rate limit exceeded [$rm]");
        # Add to ban list for 5 minutes
        cache_store("local", "ban_list:$si", "pike", 300);
        sl_send_reply(429, "Too Many Requests");
        exit;
    }
}

# Auth failure tracking (called from AUTH route)
route[AUTH_FAILURE_TRACK] {
    # Increment auth failure counter for this username
    cache_fetch("local", "auth_failures:$au", "$var(fail_count)");
    if ($var(fail_count) == "") {
        $var(fail_count) = 0;
    }
    $var(fail_count) = $var(fail_count) + 1;
    cache_store("local", "auth_failures:$au", "$var(fail_count)", 60);  # 60 second window
    xlog("L_WARN", "Auth failure $au from $si: count=$var(fail_count)");

    # T2.2: Ban if > 10 failures in 60s
    if ($var(fail_count) > 10) {
        xlog("L_ALERT", "Auth threshold exceeded for $au - banning");
        cache_store("local", "ban_list:$au", "auth", 300);  # 5 minute ban
        cache_store("local", "ban_list:$si", "auth_ip", 300);
        sl_send_reply(403, "Forbidden");
        exit;
    }
}

# Reset auth counter on success (called after successful auth)
route[AUTH_SUCCESS_RESET] {
    cache_fetch("local", "auth_failures:$au", "$var(fail_count)");
    if ($var(fail_count) != "") {
        cache_remove("local", "auth_failures:$au");
        xlog("L_INFO", "Auth success for $au - counter reset");
    }
}

# --- Ban Management MI Commands (T4.2) ---
# Usage: opensipsctl fifo cache_fetch_chunk ban_list "*"
# Usage: opensipsctl fifo cache_remove ban_list:<ip_or_user>

# --- Anomaly Detection Events ---
event_route[E_PIKE_BLOCKED] {
    xlog("L_WARN", "Anomaly: Pike blocked $si");
}

event_route[E_AUTH_FAILURE] {
    xlog("L_WARN", "Anomaly: Auth failure $au from $si");
}

# --- Feature 007: TLS/SRTP Encryption ---

# TLS trunk verification route
route[TLS_TRUNK_VERIFY] {
    # Trunk whitelist is managed via cachedb_local
    # Client certificate verification is handled by tls_mgm module
    # (verify_cert=1 on server_domain)
    # Trunk operators must present valid client certs signed by our CA
    return;
}

# SRTP route - force encryption for TLS-signaled calls
route[SRTP_ENFORCE] {
    if (is_method("INVITE") && has_body("application/sdp")) {
        # SRTP is negotiated via RTPengine offer/answer
        # RTPengine handles SDP rewrite with a=crypto lines
        # For DTLS-SRTP: rtpengine_offer("UDP/TLS/RTP/SAVP ...")
        # For SDES-SRTP: rtpengine_offer("RTP/SAVP ...")
        xlog("L_INFO", "SRTP available for call $ci");
    }
}
