-- Feedback table for user suggestions and bug reports
CREATE TABLE IF NOT EXISTS ocp_feedback (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES ocp_users(id) ON DELETE SET NULL,
    username VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'suggestion',
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'new',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_ocp_feedback_user ON ocp_feedback(user_id);
CREATE INDEX idx_ocp_feedback_type ON ocp_feedback(type);
CREATE INDEX idx_ocp_feedback_status ON ocp_feedback(status);

COMMENT ON TABLE ocp_feedback IS 'User feedback and suggestions';
