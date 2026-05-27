# TSiSIP Control Panel — Admin Guide

## Installation

### Requirements
- Docker 24.0+
- Docker Compose 2.20+
- PostgreSQL 15+
- OpenSIPS 3.6 LTS

### Setup
```bash
# Build images
docker compose build

# Start database
docker compose up -d postgres

# Run migrations
docker compose exec postgres psql -U opensips -d opensips -f /docker-entrypoint-initdb.d/07-user-preferences.sql
docker compose exec postgres psql -U opensips -d opensips -f /docker-entrypoint-initdb.d/08-user-bookmarks.sql

# Start all services
docker compose up -d

# Verify
curl http://localhost/health.php
```

## Configuration

### Environment Variables
| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | `postgres` | PostgreSQL host |
| `DB_NAME` | `opensips` | Database name |
| `DB_USER` | `opensips` | Database user |
| `OPENSIPS_MI_URL` | `http://opensips:8888/mi` | MI HTTP endpoint |
| `RTPENGINE_HOST` | `rtpengine` | RTPengine host |

### Secrets
Place in `secrets/` directory:
- `db_password` — Database password
- `auth_secret` — SIP auth secret
- `topology_secret` — Topology hiding secret

## User Management

### Creating Users
```sql
INSERT INTO ocp_users (username, email, password_hash, role, created_by)
VALUES ('newuser', 'user@tsiapp.io', crypt('temppass', gen_salt('bf')), 'readonly', 1);
```

### Roles
| Role | Level | Access |
|------|-------|--------|
| `readonly` | 1 | View only |
| `operator` | 2 | Basic operations |
| `devops` | 3 | System management |
| `admin` | 5 | Full access |

### Password Policy
- Minimum 12 characters
- Must contain uppercase, lowercase, number
- bcrypt hashing
- Force change on first login (optional)

## Monitoring

### Health Checks
- `/health.php` — Public JSON status
- `/system-health.php` — Detailed component status
- MI HTTP — OpenSIPS internal state

### Metrics
- Active dialogs
- Subscriber counts
- Login attempts
- Audit events
- Gateway status

### Alerts
Configure Prometheus Alertmanager for:
- High failed login rate
- OpenSIPS unreachable
- Database connection failures
- RTPengine errors

## Backup

### Database
```bash
docker compose exec postgres pg_dump -U opensips opensips > backup.sql
```

### Configuration
```bash
tar czf config-backup.tar.gz web/ db/ secrets/
```

## Troubleshooting

### OpenSIPS MI Unreachable
1. Check OpenSIPS container: `docker compose ps opensips`
2. Verify MI module loaded
3. Check network connectivity

### Database Connection Failed
1. Check PostgreSQL container
2. Verify credentials in secrets/
3. Check connection string

### High Memory Usage
1. Check MI cache size
2. Review SSE connections
3. Monitor active sessions

### Slow Page Loads
1. Check MI response times
2. Verify cache hit rate
3. Review database indexes

## Security Hardening

### HTTPS
- Configure reverse proxy with TLS
- Set `cookie_secure=1` in PHP
- Use HSTS headers

### Firewall
- Block port 5060 except from trusted sources
- Restrict MI HTTP to internal network
- Limit database access

### Audit
- Review audit logs regularly
- Monitor for brute force attempts
- Check for unauthorized access

## Performance Tuning

### Database
- Add indexes on frequently queried columns
- Monitor query performance
- Vacuum and analyze regularly

### Cache
- Adjust MI cache TTL (default 5s)
- Enable OPcache in PHP
- Use asset manifest for cache-busting

### SSE
- Limit concurrent connections
- Adjust heartbeat interval
- Monitor connection health

## Maintenance

### Log Rotation
```bash
# Configure logrotate for OCP logs
/var/log/tsisip/*.log {
    daily
    rotate 30
    compress
    delaycompress
}
```

### Updates
1. Pull latest images
2. Run migrations
3. Verify functionality
4. Monitor for errors

### Cleanup
- Remove old audit logs (> 90 days)
- Clear expired sessions
- Purge old export files

## Reload Operations

The OCP exposes MI reload commands for hot-reloading configuration without restarting OpenSIPS.

### Available Reloads
| Command | Page | Description |
|---------|------|-------------|
| `address_reload` | Gateway Health | Reload dispatcher address list |
| `dialplan_reload` | Dialplan | Reload dialplan rules |
| `domain_reload` | Domains | Reload domain table |
| `dr_reload` | Dynamic Routing | Reload dynamic routing rules |
| `lb_reload` | Load Balancer | Reload load-balancer targets |
| `clusterer_reload` | Clusterer | Reload clusterer nodes |
| `rtpengine_reload` | RTPengine Status | Reload RTPengine instances |
| `stats_reload` | System Reports | Reload statistics definitions |
| `siptrace_start` / `siptrace_stop` | System Reports | Toggle SIP tracing on/off |

### Usage
1. Navigate to the relevant page (requires **devops** role or higher).
2. Click the **Reload** button in the top action bar.
3. Confirm the operation in the dialog.
4. Wait for the success confirmation before proceeding.

> **Warning:** Some reloads may briefly interrupt call routing. Perform during maintenance windows when possible.

## Dialog Management

Active SIP dialogs can be inspected and terminated from the Call Queue page.

### Terminating a Dialog
1. Go to **Call Queue**.
2. Locate the dialog in the active calls table.
3. Click the **Terminate** (✕) button.
4. Confirm the termination.
5. The dialog is ended via `dlg_end_dlg` MI command.

> **Note:** Use with caution. Terminating a dialog immediately drops the call for both parties.

## UAC Registrant Actions

The UAC Registrant page lets you manage remote registrations (trunk registrations to upstream providers).

### Refresh a Registration
1. Go to **UAC Registrant**.
2. Find the registration in the list.
3. Click **Refresh** to force an immediate re-register via `uac_reg_refresh`.

### Enable or Disable a Registration
1. Select the registration row.
2. Click **Enable** or **Disable**.
3. The state is toggled via `uac_reg_enable` / `uac_reg_disable`.

## Call Center Agent Control

The Call Center page provides real-time agent management.

### Login an Agent
1. Go to **Call Center**.
2. Select the agent from the dropdown.
3. Click **Login**.
4. The agent is logged in via `cc_agent_login` MI command.

### Logout an Agent
1. Select the agent.
2. Click **Logout**.
3. The agent is logged out via `cc_agent_login` with state `0`.

> **Note:** Agents can also log in/out via SIP `*44` and `*46` feature codes.

## MI Command Executor

The **MI Commands** page (`mi-commands.php`) provides an interactive executor for whitelisted OpenSIPS MI commands.

### Access
- Requires **devops** role or higher for mutation commands.
- Read-only commands are available to all authenticated users.

### Using the Executor
1. Select a command from the dropdown (46+ commands available).
2. Fill in required parameters.
3. Click **Execute**.
4. View the structured JSON response below the form.

### Command Categories
| Category | Example Commands |
|----------|-----------------|
| System | `version`, `get_statistics`, `list_modules` |
| Dialog | `dlg_list`, `dlg_end_dlg`, `dlg_list_ctx` |
| Gateway | `ds_list`, `ds_reload`, `dr_status` |
| RTPengine | `rtpengine_show`, `rtpengine_reload` |
| Subscriber | `ul_show_contact`, `uac_reg_refresh` |
| NAT | `nh_show_active`, `nh_keepalive` |
| Topology | `th_show_locks`, `th_show_info` |
| Rate Limiting | `pike_list`, `rl_get_pipes` |
| Presence | `pres_refresh_watchers`, `list_pua` |
| TLS | `tls_list`, `tls_reload` |

> **Tip:** Hover over the info icon (ⓘ) next to each command to see parameter hints and usage examples.

## Support

For issues:
1. Check logs: `docker compose logs ocp`
2. Review audit log
3. Check health endpoint
4. Contact devops team
