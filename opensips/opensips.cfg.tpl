# TSiSIP OpenSIPS 3.6 LTS Edge Proxy Configuration
# Generated from template at container startup

# --- Network listeners ---
socket=udp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060
socket=tcp:${OPENSIPS_LISTEN_IP}:5060 as ${HOST_PUBLIC_IP}:5060
# TLS socket — habilitado (certificados gerados via ca-tool)
socket=tls:${OPENSIPS_LISTEN_IP}:5061 as ${HOST_PUBLIC_IP}:5061

# --- Database ---
db_default_url="postgres://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:5432/${DB_NAME}"

mpath="/usr/local/lib64/opensips/modules/"

# --- Modules ---
loadmodule "proto_udp.so"
loadmodule "proto_tcp.so"
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
# TLS modules (carregados mesmo que socket TLS esteja comentado)
loadmodule "tls_mgm.so"
loadmodule "tls_openssl.so"
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
modparam("rtpengine", "rtpengine_sock", "tcp:${RTPENGINE_HOST}:22223")
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

# tls_mgm — OpenSIPS 3.6 syntax: server_domain defines the domain name,
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

