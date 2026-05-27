-- User notes table for personal annotations
CREATE TABLE IF NOT EXISTS ocp_user_notes (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES ocp_users(id) ON DELETE CASCADE,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    color VARCHAR(20) DEFAULT 'yellow',
    pinned BOOLEAN DEFAULT false,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_ocp_user_notes_user ON ocp_user_notes(user_id);
CREATE INDEX idx_ocp_user_notes_pinned ON ocp_user_notes(pinned);

COMMENT ON TABLE ocp_user_notes IS 'Personal notes for users';
