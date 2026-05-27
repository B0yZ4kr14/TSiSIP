[databases]
opensips = host=${POSTGRES_HOST} port=5432 dbname=opensips

[pgbouncer]
listen_port = 5432
listen_addr = 0.0.0.0
auth_type = scram-sha-256
auth_file = /etc/pgbouncer/userlist.txt
pool_mode = transaction
max_client_conn = 1000
default_pool_size = 50
reserve_pool_size = 10
server_idle_timeout = 30
server_lifetime = 3600
log_connections = 0
log_disconnections = 0
stats_period = 60
