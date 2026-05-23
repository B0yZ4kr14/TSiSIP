# MAQA Jira Integration

> Jira board integration for the [MAQA](https://github.com/GenieRobot/spec-kit-maqa-ext) spec-kit extension.

Tracks feature progress in Jira. Each feature becomes a Story with Subtasks for individual checklist items. Stories move through your workflow as features progress.

## Requirements

- [maqa](https://github.com/GenieRobot/spec-kit-maqa-ext) extension installed
- Jira API token: id.atlassian.com → Security → API tokens → set as `JIRA_API_TOKEN`
- Also set: `JIRA_BASE_URL` (e.g. `https://your-org.atlassian.net`) and `JIRA_EMAIL`

## Installation

```bash
specify ext add maqa
specify ext add maqa-jira
```

## Setup

```bash
/speckit.maqa-jira.setup
```

Reads your Jira projects and workflow transitions, writes `maqa-jira/jira-config.yml`. Coordinator auto-activates when config and `JIRA_API_TOKEN` are present.

## License

MIT
