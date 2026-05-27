-- TSiSIP User Management Schema Extension
-- Feature 030: OCP User Management & RBAC

-- Password history to prevent reuse
CREATE TABLE IF NOT EXISTS ocp_password_history (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT fk_pwh_user FOREIGN KEY (user_id) REFERENCES ocp_users(id) ON DELETE CASCADE
);

CREATE INDEX idx_pwh_user_changed ON ocp_password_history(user_id, changed_at DESC);

-- Active sessions for invalidation support
CREATE TABLE IF NOT EXISTS ocp_user_sessions (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL,
    session_token VARCHAR(128) NOT NULL,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    last_activity TIMESTAMP NOT NULL DEFAULT NOW(),
    invalidated_at TIMESTAMP,
    CONSTRAINT fk_us_user FOREIGN KEY (user_id) REFERENCES ocp_users(id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX idx_us_token ON ocp_user_sessions(session_token);
CREATE INDEX idx_us_user_active ON ocp_user_sessions(user_id, invalidated_at) WHERE invalidated_at IS NULL;

-- Add columns to existing ocp_users if not present
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'ocp_users' AND column_name = 'password_changed_at') THEN
        ALTER TABLE ocp_users ADD COLUMN password_changed_at TIMESTAMP;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'ocp_users' AND column_name = 'deleted_at') THEN
        ALTER TABLE ocp_users ADD COLUMN deleted_at TIMESTAMP;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'ocp_users' AND column_name = 'is_active') THEN
        ALTER TABLE ocp_users ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT true;
    END IF;
END $$;
