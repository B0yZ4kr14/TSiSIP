# Work Routing

How to decide who handles what.

## Routing Table

| Work Type | Route To | Examples |
|-----------|----------|----------|
| OpenSIPS config, SIP routing, RTPengine | sip-engineer | opensips.cfg.tpl, dispatcher, topology hiding, SDP rewriting |
| PostgreSQL schema, migrations, auth data | database-engineer | db/init/*.sql, subscriber schema, HA1 hashes, query tuning |
| OCP admin tools, PHP frontend, web UI | frontend-engineer | web/*.php, RBAC, CSRF, D3.js charts, dashboard |
| Docker images, Compose, CI/CD, deploy | devops-engineer | Dockerfile, docker-compose.vps.yml, deploy/scripts, GitHub Actions |
| Auth protocols, TLS, SRTP, audit, threat model | security-engineer | security assessments, brownfield scans, cert rotation, header sanitization |
| SIP tests, integration tests, brownfield scans | qa-engineer | tests/integration/*.py, sipsak validation, AC verification |
| Documentation, runbooks, spec maintenance | scribe | docs/*.md, AGENTS.md updates, evidence indexing |
| Cross-session memory, context continuity | ralph | Session state, persistent context, historical decisions |
| Code review | squad | Review PRs, check quality, suggest improvements |
| Scope & priorities | squad | What to build next, trade-offs, architecture decisions |
| Session logging | scribe | Automatic — never needs routing |

## Issue Routing

| Label | Action | Who |
|-------|--------|-----|
| squad | Triage: analyze issue, assign squad:member label | Coordinator |
| squad:sip-engineer | OpenSIPS or RTPengine work | sip-engineer |
| squad:database-engineer | PostgreSQL schema or query work | database-engineer |
| squad:frontend-engineer | OCP PHP or web UI work | frontend-engineer |
| squad:devops-engineer | Docker or deployment work | devops-engineer |
| squad:security-engineer | Auth, TLS, or audit work | security-engineer |
| squad:qa-engineer | Testing or validation work | qa-engineer |

### How Issue Assignment Works

1. When a GitHub issue gets the squad label, the Coordinator triages it — analyzing content, assigning the right squad:member label, and commenting with triage notes.
2. When a squad:member label is applied, that member picks up the issue in their next session.
3. Members can reassign by removing their label and adding another member's label.
4. The squad label is the inbox — untriaged issues waiting for Coordinator review.

## Rules

1. **Eager by default** — spawn all agents who could usefully start work, including anticipatory downstream work.
2. **Scribe always runs** after substantial work, always as mode: background. Never blocks.
3. **Quick facts → coordinator answers directly.** Do not spawn an agent for "what port does OpenSIPS listen on?"
4. **When two agents could handle it**, pick the one whose domain is the primary concern.
5. **Team, ... → fan-out.** Spawn all relevant agents in parallel as mode: background.
6. **Anticipate downstream work.** If a feature is being built, spawn the tester to write test cases from requirements simultaneously.
7. **Issue-labeled work** — when a squad:member label is applied to an issue, route to that member. The Coordinator handles all squad (base label) triage.
