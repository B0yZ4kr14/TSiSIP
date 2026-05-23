# MAQA Jira Changelog

## 0.1.0 — 2026-03-26

Initial release.

- Setup command: reads Jira projects and workflow transitions via REST API v3, generates jira-config.yml
- Populate command: creates Jira stories from specs/*/tasks.md with subtasks per task; skips existing; safe to re-run
- Coordinator integration: auto-detected when jira-config.yml + JIRA_API_TOKEN present
