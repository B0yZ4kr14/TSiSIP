# Work IQ — Spec Kit Extension

[![Spec Kit Extension](https://img.shields.io/badge/spec--kit-extension-blue)](https://github.com/github/spec-kit)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.0.0-green.svg)](CHANGELOG.md)

Bring Microsoft 365 organizational knowledge into your [Spec Kit](https://github.com/github/spec-kit) spec-driven development workflow. Query emails, meetings, documents, and Teams messages to write better specs, faster.

## What It Does

| Command | Description |
|---------|-------------|
| `/speckit.workiq.ask` | Ask any question about your M365 data |
| `/speckit.workiq.context` | Gather context from emails, meetings, docs, and Teams for a topic |
| `/speckit.workiq.stakeholders` | Discover decision-makers, SMEs, and contributors |
| `/speckit.workiq.enrich` | Find gaps in an existing spec using M365 organizational knowledge |

## Prerequisites

- [Spec Kit](https://github.com/github/spec-kit) installed and configured
- [Node.js](https://nodejs.org/) 18 or later
- [Microsoft Work IQ CLI](https://github.com/microsoft/work-iq) installed:
  ```bash
  npm install -g @microsoft/workiq
  workiq accept-eula
  ```
- Microsoft 365 subscription with Copilot license
- Tenant admin consent for Work IQ (see [Work IQ docs](https://github.com/microsoft/work-iq))

## Installation

Download the latest release and install with Spec Kit:

```bash
specify extension add workiq --from https://github.com/sakitA/spec-kit-workiq/archive/refs/tags/v1.0.0.zip
```

Or clone and install locally:

```bash
git clone https://github.com/sakitA/spec-kit-workiq.git
specify extension add workiq --from ./spec-kit-workiq
```

## Quick Start

### Ask a question

```
/speckit.workiq.ask What decisions were made about the auth redesign?
```

### Gather context for a new spec

```
/speckit.workiq.context payment processing migration
```

### Find who should review your spec

```
/speckit.workiq.stakeholders API gateway modernization
```

### Enrich an existing spec

```
/speckit.workiq.enrich
```

## How It Works

The extension uses [Microsoft Work IQ](https://github.com/microsoft/work-iq) to query your M365 data. It operates in two modes:

1. **MCP Server** (preferred): When running inside an AI agent with the Work IQ MCP server configured, commands use the `workiq-ask_work_iq` tool directly.
2. **CLI Fallback**: When the MCP server isn't available, commands fall back to `workiq ask -q "<question>"` in the terminal.

All outputs are saved to `.specify/context/` for traceability:

| File | Source Command |
|------|---------------|
| `workiq-ask-log.md` | `/speckit.workiq.ask` |
| `workiq-context.md` | `/speckit.workiq.context` |
| `workiq-stakeholders.md` | `/speckit.workiq.stakeholders` |
| `workiq-enrichment.md` | `/speckit.workiq.enrich` |

## Workflow Hooks

The extension provides optional workflow hooks:

- **`before_specify`**: Suggests gathering M365 context before writing a spec
- **`after_specify`**: Suggests enriching the spec with M365 data after the initial draft

## Configuration

Copy `config-template.yml` to `config.yml` to customize defaults:

```yaml
output_dir: ".specify/context"
save_results: true
default_lookback_days: 30
data_sources:
  emails: true
  meetings: true
  documents: true
  teams: true
```

## Privacy & Security

- Work IQ inherits your Microsoft 365 permissions — you only see data you already have access to
- The extension stores **summaries only**, never raw email or message content
- No data is sent to third-party services
- All queries are processed through Microsoft's enterprise-grade infrastructure
- See the [Work IQ security documentation](https://github.com/microsoft/work-iq) for details

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "workiq: command not found" | Run `npm install -g @microsoft/workiq` |
| Authentication errors | Run `workiq auth` or check your M365 Copilot license |
| No results returned | Broaden your search terms or extend the time range |
| EULA not accepted | Run `workiq accept-eula` |
| Node.js version error | Upgrade to Node.js 18+ |

## Documentation

- [Detailed Usage Guide](docs/usage.md) — In-depth examples and data flow
- [Spec Kit Extension Guide](https://github.com/github/spec-kit/blob/main/extensions/EXTENSION-PUBLISHING-GUIDE.md) — How Spec Kit extensions work
- [Work IQ Documentation](https://github.com/microsoft/work-iq) — Work IQ CLI and MCP server

## Contributing

Contributions are welcome! Please open an issue or pull request on [GitHub](https://github.com/sakitA/spec-kit-workiq).

## License

[MIT](LICENSE)
