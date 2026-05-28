-- Feature 035: Dispatcher Change Log for Rollback Support
CREATE TABLE IF NOT EXISTS dispatcher_change_log (
    id SERIAL PRIMARY KEY,
    user_id UUID REFERENCES ocp_users(id) ON DELETE SET NULL,
    username VARCHAR(64) NOT NULL DEFAULT '',
    action VARCHAR(16) NOT NULL CHECK (action IN ('ADD', 'UPDATE', 'DELETE', 'RELOAD', 'ROLLBACK', 'IMPORT')),
    setid INTEGER NOT NULL,
    destination_id INTEGER,
    old_snapshot JSONB,
    new_snapshot JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_dispatcher_change_log_setid
    ON dispatcher_change_log(setid, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_dispatcher_change_log_user
    ON dispatcher_change_log(user_id, created_at DESC);

-- Retention: keep last 90 days of change logs
-- A cron job or pg_cron task should run:
--   DELETE FROM dispatcher_change_log WHERE created_at < NOW() - INTERVAL '90 days';
