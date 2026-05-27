-- TSiSIP OCP REST API — API Keys Schema
-- Feature 031

CREATE TABLE IF NOT EXISTS ocp_api_keys (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    scope VARCHAR(20) NOT NULL DEFAULT 'readonly' CHECK (scope IN ('readonly', 'readwrite')),
    created_by UUID REFERENCES ocp_users(id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    expires_at TIMESTAMP,
    last_used_at TIMESTAMP,
    is_active BOOLEAN NOT NULL DEFAULT true,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_api_keys_active ON ocp_api_keys(is_active) WHERE is_active = true AND deleted_at IS NULL;
CREATE INDEX idx_api_keys_hash ON ocp_api_keys(key_hash);
