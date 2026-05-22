# Constitution Reference Template

> Include this section at the end of every spec.md to ensure traceability to governance and architecture principles.

## Constitution Alignment

### Governance Principles
- [ ] This feature does not violate any MUST principle in `.specify/memory/constitution.md`
- [ ] This feature references the relevant sections of `.specify/memory/security_constitution.md`

### Architecture Principles
- [ ] This feature respects the layer boundaries defined in `.specify/memory/architecture_constitution.md`
- [ ] Docker-first delivery is maintained (no bare-metal or VM-first alternatives introduced)
- [ ] PostgreSQL-only persistence is maintained (no db_mysql or db_sqlite references)

### Security Principles
- [ ] No plaintext passwords; HA1-only for SIP, bcrypt for OCP
- [ ] No host-published ports for PostgreSQL or Asterisk
- [ ] Header sanitization requirements are defined if SIP forwarding is involved
- [ ] Audit logging requirements are defined if state-mutating operations are introduced

### Rejected Patterns Check
- [ ] No `sanity` module references
- [ ] No `db_mysql` module references
- [ ] No `:latest` or `:stable` image tags
- [ ] No hardcoded secrets or credentials

## Cross-References
- Governance: `.specify/memory/constitution.md`
- Architecture: `.specify/memory/architecture_constitution.md`
- Security: `.specify/memory/security_constitution.md`
