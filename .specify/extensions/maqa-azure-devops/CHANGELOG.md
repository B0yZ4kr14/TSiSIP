# MAQA Azure DevOps Changelog

## 0.1.0 — 2026-03-26

Initial release.

- Setup command: reads Azure DevOps organization, project, and board columns via REST API
- Populate command: creates User Stories from specs/*/tasks.md with Task child items; skips existing; safe to re-run
- Coordinator integration: auto-detected when azure-devops-config.yml + AZURE_DEVOPS_TOKEN present
