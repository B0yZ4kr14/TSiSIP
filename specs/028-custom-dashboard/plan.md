# Plan: Custom Dashboard Layouts

## Architecture

- Widget registry in `web/tsisip/js/dashboard-widgets.js`
- Preference storage: localStorage + server-side `ocp_user_preferences` table
- Server sync via `web/common/save-dashboard.php`

## Widgets

| ID | Label | Default |
|---|---|---|
| status | System Status | true |
| management | System Management | true |
| newtools | Feature 002 Tools | true |
| wiki | Documentation | true |
| bookmarks | My Bookmarks | true |
| activity | Recent Activity | true |
| runtime | Runtime Monitoring | true |
| security | Security | true |

## Data Model

Table: `ocp_user_preferences`
- user_id INT FK
- preference_key VARCHAR(50) DEFAULT 'dashboard_layout'
- preference_value JSONB
