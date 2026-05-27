# TSiSIP Database Guide

## Schema

### Tables

#### subscriber
SIP user accounts.

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Primary key |
| username | VARCHAR | SIP username |
| domain | VARCHAR | SIP domain |
| ha1 | VARCHAR | MD5 hash |
| tenant_id | INT | Tenant reference |

#### ocp_users
Control panel users.

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Primary key |
| username | VARCHAR | Login name |
| password_hash | VARCHAR | bcrypt hash |
| role | VARCHAR | User role |
| email | VARCHAR | Email |

#### ocp_audit_log
Activity tracking.

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Primary key |
| event_time | TIMESTAMPTZ | Timestamp |
| username | VARCHAR | User |
| action | VARCHAR | Action |
| resource_type | VARCHAR | Resource type |
| resource_id | VARCHAR | Resource ID |
| success | BOOLEAN | Success |

#### ocp_user_preferences
User settings.

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Primary key |
| user_id | INT | User reference |
| preference_key | VARCHAR | Setting name |
| preference_value | JSONB | Setting value |

#### ocp_user_bookmarks
Saved pages.

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Primary key |
| user_id | INT | User reference |
| page_url | VARCHAR | Page URL |
| page_label | VARCHAR | Label |

#### ocp_feedback
User feedback.

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Primary key |
| user_id | INT | User reference |
| type | VARCHAR | Feedback type |
| message | TEXT | Content |
| status | VARCHAR | Status |

#### ocp_user_notes
Personal notes.

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Primary key |
| user_id | INT | User reference |
| title | VARCHAR | Title |
| content | TEXT | Content |
| color | VARCHAR | Color |
| pinned | BOOLEAN | Pinned |

### Indexes

```sql
CREATE INDEX idx_subscriber_username ON subscriber(username);
CREATE INDEX idx_audit_time ON ocp_audit_log(event_time);
CREATE INDEX idx_audit_user ON ocp_audit_log(username);
```

## Migrations

### Running
```bash
bash scripts/migrate.sh
```

### Adding
1. Create `db/init/NN-name.sql`
2. Run migration
3. Commit

### Order
Files run alphabetically. Use `NN-` prefix.

## Backup

### Automated
```bash
bash scripts/backup-db.sh
```

### Manual
```bash
docker compose exec postgres pg_dump -U opensips opensips > backup.sql
```

## Restore

```bash
bash scripts/restore-db.sh backups/tsisip_db_YYYYMMDD_HHMMSS.sql.gz
```

## Performance

### VACUUM
```bash
docker compose exec postgres psql -U opensips -c "VACUUM ANALYZE;"
```

### Indexes
```sql
SELECT schemaname, tablename, indexname FROM pg_indexes;
```

## Troubleshooting

### Connection Failed
- Check container status
- Verify credentials
- Check network

### Slow Queries
- Add indexes
- Check query plan
- Optimize queries

### Disk Full
- Clean old logs
- Archive data
- Expand volume
