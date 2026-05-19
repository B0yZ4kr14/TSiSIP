# TSiSIP Professional Wiki

> Premium operational wiki for the TSiSIP SIP edge platform.

## Current Production Posture

TSiSIP is deployed on VPS TSiAPP using the `docker-compose.vps.yml` vps-lite+PBX profile. The host stack is healthy and runs the TSiSIP SIP edge service, RTPengine, PostgreSQL, OCP, backup, and two internal Asterisk PBX services.

The SIP edge is host-ready but not fully public: `5060/udp`, `5060/tcp`, and `5061/tcp` listen on the VPS, while external scans still show 5060/5061 as filtered before packets reach the host. That remaining exposure is an upstream provider/NAT/edge ACL task.

## Audience Map

| Audience | Start Here | Purpose |
|---|---|---|
| Executives / Owners | [System Overview](system-overview.md) | Understand business role, scope, and readiness |
| DevOps SIP | [DevOps SIP Guide](devops-sip.md) | Operate SIP edge, media, routing, backup, and observability |
| Administrators | [Administrator Guide](administrators.md) | Manage services, access, backups, and routine checks |
| Operators / Users | [Operator and User Guide](operators-users.md) | Use OCP and understand expected platform behavior |
| Dentists | [Dentist Clinical Operator Guide](dentists.md) | Verify endpoints, monitor call quality, and escalate clinical issues |
| Assistants | [Front-Desk Operator Guide](assistants.md) | Daily health checks, trunk verification, and patient call routing |
| Security / Compliance | [Security and Compliance](security-compliance.md) | Review controls, secrets, network isolation, and open risks |
| Developers | [Developer Guide](developers.md) | Work safely on code, specs, tests, and GitNexus analysis |
| Incidents | [Runbooks and Troubleshooting](runbooks-troubleshooting.md) | Resolve common failures using tested command paths |

## Canonical Source Files

- `STATUS.md`: current project and VPS state.
- `docs/TSiSIP-CANONICAL-SPEC.md`: architecture baseline and non-negotiable rules.
- `docs/TSiSIP-OPERATOR-RUNBOOK.md`: operational procedures.
- `deploy/VPS-DEPLOY-READINESS.md`: VPS readiness and remaining gates.
- `reports/vps-production-validation-2026-05-19.md`: live validation evidence.
- `specs/`: feature-level specs, plans, tasks, and checklists.

## Evidence Policy

This wiki separates proven state from pending state:

- **Proven**: validated by command output, host inspection, GitNexus analysis, or recorded report.
- **Host-ready**: configured and healthy on the VPS, but dependent on upstream routing or external services.
- **Pending**: not yet observed in production conditions, such as cron windows, PITR live restore, offsite replication, or upstream SIP exposure.
