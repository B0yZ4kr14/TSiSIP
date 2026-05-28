-- Feature 037: Multi-Factor Authentication (MFA) for OCP

-- MFA enrollment per user
CREATE TABLE IF NOT EXISTS ocp_user_mfa (
    user_id UUID PRIMARY KEY REFERENCES ocp_users(id) ON DELETE CASCADE,
    secret_encrypted TEXT NOT NULL,
    enabled_at TIMESTAMP WITH TIME ZONE,
    last_verified_at TIMESTAMP WITH TIME ZONE,
    failed_attempts INTEGER DEFAULT 0,
    locked_until TIMESTAMP WITH TIME ZONE,
    last_code_window INTEGER,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Backup/recovery codes
CREATE TABLE IF NOT EXISTS ocp_user_backup_codes (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES ocp_users(id) ON DELETE CASCADE,
    code_hash VARCHAR(255) NOT NULL,
    used_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_backup_codes_user ON ocp_user_backup_codes(user_id, used_at);

-- MFA policy per role
CREATE TABLE IF NOT EXISTS mfa_policy (
    role VARCHAR(32) PRIMARY KEY,
    enforced BOOLEAN DEFAULT false,
    grace_period_days INTEGER DEFAULT 7,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insert default policies
INSERT INTO mfa_policy (role, enforced, grace_period_days) VALUES
    ('admin', true, 7),
    ('devops', true, 7),
    ('dentist', false, 7),
    ('assistant', false, 7),
    ('user', false, 7),
    ('readonly', false, 7)
ON CONFLICT (role) DO NOTHING;
