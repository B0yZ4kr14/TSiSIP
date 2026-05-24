# Feature Specification: {{FEATURE_NAME}}

## Overview

**Feature**: {{FEATURE_NAME}}
**Short name**: {{SHORT_NAME}}
**Created**: {{DATE}}
**Status**: Draft

### Context

[Describe the business context and why this feature is needed.]

### Objective

[What problem does this feature solve? What value does it deliver to users or the business?]

---

## User Scenarios & Testing

### Primary Flows

#### Scenario 1: [Primary user goal]
- **Given** [initial context]
- **When** [user action]
- **Then** [expected outcome]

#### Scenario 2: [Secondary user goal]
- **Given** [initial context]
- **When** [user action]
- **Then** [expected outcome]

### Edge Cases & Error Conditions

- [Edge case 1: describe unusual but possible situation]
- [Edge case 2: describe failure mode]
- [Edge case 3: describe security or abuse scenario]

---

## Functional Requirements

### FR-001: [Requirement title]
**Description**: [Clear, testable description of what the system must do.]
**Acceptance Criteria**:
- [Criterion 1]
- [Criterion 2]

### FR-002: [Requirement title]
**Description**: [Clear, testable description.]
**Acceptance Criteria**:
- [Criterion 1]
- [Criterion 2]

### FR-003: [Requirement title]
**Description**: [Clear, testable description.]
**Acceptance Criteria**:
- [Criterion 1]
- [Criterion 2]

---

## Security Requirements

| ID | Requirement | Verification Method |
|---|---|---|
| SR-001 | No host-published ports on Asterisk or PostgreSQL | `docker compose config` + port audit |
| SR-002 | Auth uses precomputed HA1 only (`calculate_ha1 = 0`) | `opensips -c` + schema verification |
| SR-003 | Secrets not committed (secrets/, .env*) | `.gitignore` + secret-leakage scan |
| SR-004 | RTPengine control binds to internal IP only | `docker compose config` + nmap verification |
| SR-005 | Header sanitization strips untrusted inbound headers | Code review + negative test |

## Docker & Infrastructure Requirements

### Network Topology
- Which networks: `sip_edge` (public), `sip_internal` (private), `db_internal` (private)
- Any new networks? Specify CIDR and whether they are `internal: true`.

### Container Configuration
- Base image tag: must be pinned (not `:latest` unless explicitly justified)
- `cap_drop: [ALL]` required unless documented exception
- `security_opt: ["no-new-privileges:true"]` required
- `restart` policy: `unless-stopped`, `on-failure`, or `always` with justification

### OpenSIPS Module Requirements
- List new `loadmodule` entries and `modparam` configurations
- Verify every module is documented for OpenSIPS 3.6 LTS
- Forbidden modules: `sanity`, `db_mysql`, `rtpproxy` (unless ADR exists)

---

## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-001 | [User-facing outcome] | [How measured] | [Target value] |
| SC-002 | [Performance outcome] | [How measured] | [Target value] |
| SC-003 | [Security/quality outcome] | [How measured] | [Target value] |

---

## Key Entities

[If the feature involves data models, list the key entities and their relationships.]

### Entity: [Entity Name]
- **Attributes**: [list]
- **Relationships**: [to other entities]

---

## Scope

### In Scope
- [Item 1]
- [Item 2]

### Out of Scope
- [Item 1]
- [Item 2]

---

## Dependencies

- [Dependency 1: external system, team, or feature]
- [Dependency 2: infrastructure or platform requirement]

---

## Assumptions

- [Assumption 1: reasonable default or prerequisite]
- [Assumption 2: reasonable default or prerequisite]

---

## Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| [Risk description] | High/Medium/Low | High/Medium/Low | [Mitigation strategy] |

---

## Notes

[Additional context, references, or open questions.]
