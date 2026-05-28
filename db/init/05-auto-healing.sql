-- Feature 036: Auto-Healing SIP Infrastructure

-- Health probe history
CREATE TABLE IF NOT EXISTS dispatcher_health_log (
    id SERIAL PRIMARY KEY,
    destination_id INTEGER REFERENCES dispatcher(id) ON DELETE CASCADE,
    setid INTEGER NOT NULL,
    destination VARCHAR(192) NOT NULL,
    reachable BOOLEAN,
    sip_code INTEGER,
    rtt_ms NUMERIC(8,2),
    failure_count INTEGER DEFAULT 0,
    checked_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_dispatcher_health_log_dest
    ON dispatcher_health_log(destination_id, checked_at DESC);
CREATE INDEX IF NOT EXISTS idx_dispatcher_health_log_setid
    ON dispatcher_health_log(setid, checked_at DESC);

-- Auto-healing configuration
CREATE TABLE IF NOT EXISTS autoheal_config (
    key VARCHAR(64) PRIMARY KEY,
    value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insert defaults
INSERT INTO autoheal_config (key, value, description) VALUES
    ('probe_interval_sec', '60', 'Seconds between health monitor cycles'),
    ('probe_timeout_sec', '3', 'Timeout per SIP OPTIONS probe'),
    ('auto_rollback_window_min', '15', 'Minutes to look back for auto-rollback'),
    ('auto_failover_threshold', '5', 'Consecutive failures before auto-failover'),
    ('auto_failover_window_min', '10', 'Time window for consecutive failures'),
    ('circuit_breaker_failures', '3', 'Failed auto-heal actions before circuit opens'),
    ('circuit_breaker_cooldown_min', '30', 'Minutes circuit breaker stays open')
ON CONFLICT (key) DO NOTHING;
