-- User bookmarks table for quick access to favorite pages
CREATE TABLE IF NOT EXISTS ocp_user_bookmarks (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES ocp_users(id) ON DELETE CASCADE,
    page_url VARCHAR(255) NOT NULL,
    page_label VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'star',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(user_id, page_url)
);

CREATE INDEX idx_ocp_user_bookmarks_user ON ocp_user_bookmarks(user_id);

-- Default bookmarks for admin
INSERT INTO ocp_user_bookmarks (user_id, page_url, page_label, icon, sort_order)
SELECT id, 'system-health.php', 'System Health', 'activity', 1
FROM ocp_users WHERE username = 'admin'
ON CONFLICT DO NOTHING;

INSERT INTO ocp_user_bookmarks (user_id, page_url, page_label, icon, sort_order)
SELECT id, 'gateway-health.php', 'Gateway Health', 'route', 2
FROM ocp_users WHERE username = 'admin'
ON CONFLICT DO NOTHING;

INSERT INTO ocp_user_bookmarks (user_id, page_url, page_label, icon, sort_order)
SELECT id, 'audit-log.php', 'Audit Log', 'history', 3
FROM ocp_users WHERE username = 'admin'
ON CONFLICT DO NOTHING;
