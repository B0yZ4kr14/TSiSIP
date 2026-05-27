-- User preferences table for custom dashboard layouts
CREATE TABLE IF NOT EXISTS ocp_user_preferences (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES ocp_users(id) ON DELETE CASCADE,
    preference_key VARCHAR(50) NOT NULL DEFAULT 'dashboard_layout',
    preference_value JSONB NOT NULL DEFAULT '{}',
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(user_id, preference_key)
);

CREATE INDEX idx_ocp_user_prefs_user ON ocp_user_preferences(user_id);
CREATE INDEX idx_ocp_user_prefs_key ON ocp_user_preferences(preference_key);

COMMENT ON TABLE ocp_user_preferences IS 'Per-user UI preferences including dashboard layout';
