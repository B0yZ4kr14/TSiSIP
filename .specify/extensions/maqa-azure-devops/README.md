# MAQA Azure DevOps Integration

> Azure DevOps Boards integration for the [MAQA](https://github.com/GenieRobot/spec-kit-maqa-ext) spec-kit extension.

Tracks feature progress on an Azure DevOps board. Each feature becomes a User Story with Task child items. Stories move through board columns as features progress.

## Requirements

- [maqa](https://github.com/GenieRobot/spec-kit-maqa-ext) extension installed
- Azure DevOps PAT with Work Items read/write scope: set as `AZURE_DEVOPS_TOKEN`

## Installation

```bash
specify ext add maqa
specify ext add maqa-azure-devops
```

## Setup

```bash
/speckit.maqa-azure-devops.setup
```

Reads your Azure DevOps organization, project, and board columns, writes `maqa-azure-devops/azure-devops-config.yml`. Coordinator auto-activates when config and `AZURE_DEVOPS_TOKEN` are present.

## License

MIT
