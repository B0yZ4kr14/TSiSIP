# TSiSIP Evidence Tree

*Generated: 2026-05-25T12:38:19.790954Z*

This directory contains the complete BrownKit EDCR pipeline evidence for TSiSIP.

## Phase Index

| Phase | Status | Key Artifacts |
|---|---|---|
| `/init` | completed | [context.json](context.json), [workflow.json](workflow.json) |
| `/scan` | completed | [discovery/candidates.md](discovery/candidates.md), [security/security-signals.json](security/security-signals.json), [qa/qa-signals.json](qa/qa-signals.json) |
| `/discover` | completed | [discovery/l1-capabilities.md](discovery/l1-capabilities.md), [discovery/domain-model.md](discovery/domain-model.md) |
| `/report` | completed | [reports/stakeholder-report.md](reports/stakeholder-report.md), [reports/architect-report.md](reports/architect-report.md), [reports/dev-report.md](reports/dev-report.md), [reports/sdet-report.md](reports/sdet-report.md) |
| `/assess` | completed | [security/risk-scores.json](security/risk-scores.json), [risk/unified-risk-map.json](risk/unified-risk-map.json) |
| `/finish` | completed | [acceptance-check.md](acceptance-check.md), [manifest.json](manifest.json) |

## Directory Structure

```
evidence/
├── context.json              # Project scope, security scope, QA scope
├── workflow.json             # Pipeline phase tracking
├── acceptance-check.md       # 14-point acceptance validation
├── manifest.json             # Machine-readable artifact index
├── README.md                 # This file
├── discovery/                # Capability discovery artifacts
│   ├── candidates.md
│   ├── analysis.md
│   ├── coverage.md
│   ├── l1-capabilities.md
│   ├── l2-capabilities.md
│   ├── domain-model.md
│   ├── blueprint-comparison.md
│   ├── security-context.json
│   └── signals/
├── security/                 # Security signals and assessment
│   ├── security-signals.json
│   ├── security-dependencies.json
│   ├── risk-scores.json
│   ├── cross-capability-risks.json
│   ├── gaps.json
│   ├── controls/
│   ├── threats/
│   └── vulnerabilities/
├── qa/                       # QA signals and assessment
│   ├── qa-signals.json
│   ├── qa-context.json
│   ├── qa-risk-scores.json
│   ├── qa-gaps.json
│   ├── test-inventory.json
│   ├── coverage/
│   ├── testability/
│   └── environments/
├── risk/                     # Unified risk scoring
│   └── unified-risk-map.json
├── reports/                  # Audience-specific reports
│   ├── stakeholder-report.md
│   ├── architect-report.md
│   ├── dev-report.md
│   └── sdet-report.md
└── generate/                 # Handoff bundles
    └── handoff/
```
