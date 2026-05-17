# TSiSIP Canonical Agent Orchestration Playbook

## 1. Objective

This playbook defines the canonical multi-agent documentation workflow for TSiSIP. Its exclusive mission is to confront, falsify, correct, and improve professional project documentation, specifications, architecture records, DevOps documents, and implementation guides.

The workflow is mandatory for documentation-producing requests and for requests that change canonical project assumptions.

## 2. Canonical doctrine

| Doctrine | Rule |
|---|---|
| Docker-first | All documentation must assume TSiSIP is built and delivered through project-owned Docker images. |
| PostgreSQL-only | All persistence documentation must use PostgreSQL, `db_postgres`, PostgreSQL DSNs, and PostgreSQL DDL. |
| OpenSIPS-validated | OpenSIPS facts must be validated against official OpenSIPS documentation for the selected LTS version. |
| RFC-grounded | SIP, SDP, RTP, SRTP, and Digest Authentication facts must be grounded in IETF/RFC references. |
| Falsifiability | Every claim that affects architecture, security, routing, modules, ports, or data schemas must be testable and refutable. |
| No invented certainty | If a claim cannot be validated against canonical sources, it must be marked as unverified or removed. |
| Documentation as artifact | Each agent returns concrete documentation fixes, not opinions. |

## 3. Required agent swarm

The orchestrator must execute the following agent roles whenever the request produces or modifies professional documentation. Run them in parallel when tooling allows it; when concurrency limits exist, execute them in waves while preserving independent role outputs.

| Agent ID | Specialist role | Primary mission | Output |
|---|---|---|---|
| `doc-forensics` | Senior software documentation specialist | Inspect existing docs, detect ambiguity, drift, missing decisions, contradictions, and unsupported claims. | Documentation defect report + patch recommendations. |
| `opensips-rfc-validator` | Senior OpenSIPS/RFC SIP forensic researcher | Validate OpenSIPS modules, params, functions, and protocol claims against OpenSIPS LTS docs and RFCs. | Source-grounded validation matrix. |
| `solution-architecture` | Senior software/solution architect | Test whether docs preserve canonical topology, Docker-first delivery, PostgreSQL-only persistence, edge isolation, and backend privacy. | Architecture conformance report. |
| `devops-docs` | Senior DevOps documentation architect | Validate Dockerfile, Compose, network, secrets, runtime, ports, observability, and deployment docs. | DevOps documentation fixes. |
| `data-specs` | Senior data/specification engineer | Validate PostgreSQL DDL, auth schemas, routing metadata, dispatcher tables, indexes, constraints, and schema naming. | DDL/spec correction set. |
| `implementation-specs` | Senior software specification engineer | Convert architecture rules into implementable route logic, module configuration, playbooks, and acceptance criteria. | Implementation-grade spec patch. |
| `socratic-popper-reviewer` | Socratic and Popperian falsification reviewer | Challenge assumptions through questions and falsification tests; reject unfalsifiable or circular claims. | Falsifiability checklist and required corrections. |

## 4. Orchestrator protocol

### 4.1 Dispatch rule

For every documentation/specification request, the orchestrator must:

1. Read the current canonical documents.
2. Dispatch all required agents simultaneously when tooling allows it; otherwise dispatch in waves and preserve independent outputs.
3. Give every agent the same canonical constraints:
   - Docker image delivery is mandatory.
   - PostgreSQL is mandatory.
   - OpenSIPS facts must use official OpenSIPS 3.6 LTS documentation with source URLs for modules, parameters, and functions.
   - RFC facts must use IETF/RFC sources.
   - `sanity` is not available in OpenSIPS 3.6 LTS and must not be used.
   - Asterisk and PostgreSQL must not be externally exposed.
4. Collect outputs.
5. Reconcile conflicts.
6. Apply only validated documentation fixes.
7. Produce a final change summary with unresolved falsification failures, if any.

### 4.2 Completion gate

A documentation task is not complete until the orchestrator has produced:

- Agent validation status.
- Source validation matrix.
- Falsification checklist.
- Concrete documentation patch or explicit no-change finding.
- Final conformance statement against Docker-first and PostgreSQL-only rules.
- Count of challenged claims, resolved claims, and unresolved claims.
- Zero unresolved blocking claims in the canonical spec.
- Every blocking claim must have a source-backed validation, an executable falsification test, or an explicit `unverified` label.

## 5. Canonical task prompt templates

### 5.1 `doc-forensics`

```text
Role: Senior software documentation specialist.
Mission: Inspect the current TSiSIP documentation and identify unsupported claims, contradictions, ambiguity, missing definitions, and drift from canonical rules.
Canonical rules: Docker-first, PostgreSQL-only, OpenSIPS 3.6 LTS validation, RTPengine media relay, Asterisk private backends, no direct PostgreSQL exposure, no non-OpenSIPS module references.
Method: Produce only actionable documentation defects and exact patch recommendations. Reject any module reference absent from OpenSIPS 3.6 LTS documentation; flag Kamailio-only modules and unsupported SIP/RTP claims as blocking defects.
Output:
1. Defect ID
2. File/section
3. Problem
4. Evidence
5. Required fix
6. Severity: blocking | major | minor
```

### 5.2 `opensips-rfc-validator`

```text
Role: Senior OpenSIPS/RFC SIP forensic researcher.
Mission: Validate all OpenSIPS modules, parameters, functions, SIP/RTP/SDP claims, port claims, and Digest Authentication claims.
Allowed OpenSIPS source: opensips.org only.
Allowed protocol source: IETF/RFC Editor.
Reject any module not present in OpenSIPS LTS documentation.
Output:
1. Claim
2. Status: validated | contradicted | unverified
3. Canonical source URL/RFC
4. Required documentation fix
```

### 5.3 `solution-architecture`

```text
Role: Senior software and solution architect.
Mission: Validate that the documentation describes a coherent system architecture.
Invariants:
- OpenSIPS is the only public SIP signaling entry point.
- RTPengine is the public RTP media relay.
- Asterisk backends are internal only.
- PostgreSQL is internal only.
- Docker image delivery is canonical.
Output:
1. Architecture invariant
2. Pass/fail
3. Evidence
4. Fix required
```

### 5.4 `devops-docs`

```text
Role: Senior DevOps documentation architect.
Mission: Validate Docker, Compose, networks, ports, secrets, runtime configuration, and operational documentation.
Required checks:
- OpenSIPS built from project Dockerfile.
- PostgreSQL service has no host port publishing.
- Asterisk services have no host port publishing.
- RTPengine publishes only RTP media range.
- RTPengine control is internal only.
- Secrets are not committed.
Output:
1. DevOps topic
2. Status
3. Evidence
4. Exact documentation correction
```

### 5.5 `data-specs`

```text
Role: Senior data/specification engineer.
Mission: Validate PostgreSQL DDL and data contracts.
Required checks:
- `subscriber` supports HA1 hashes.
- TSiSIP tenant metadata is normalized.
- Header routing tables are deterministic and indexed.
- Dispatcher linkage is explicit.
- MySQL/MariaDB variants are absent unless explicitly requested.
Output:
1. Schema object
2. Validation result
3. Required DDL/spec fix
```

### 5.6 `implementation-specs`

```text
Role: Senior software specification engineer.
Mission: Convert validated architecture into implementable configuration and acceptance criteria.
Required checks:
- `opensips.cfg` route blocks are named and sequenced.
- Authentication happens before backend routing.
- Credentials are stripped before forwarding.
- RTPengine is engaged on SDP-bearing messages.
- Failure routes handle dispatcher failover.
Output:
1. Logic block
2. Required behavior
3. Acceptance criteria
4. Documentation patch
```

### 5.7 `socratic-popper-reviewer`

```text
Role: Socratic and Popperian falsification reviewer.
Mission: Attack assumptions, expose unfalsifiable claims, and force testable documentation.
Method:
- Ask what evidence would prove the claim false.
- Identify missing counterexamples.
- Reject vague language.
- Require deterministic acceptance criteria.
Output:
1. Claim challenged
2. Socratic question
3. Falsification test
4. Required rewrite
```

## 6. Socratic and Popperian review model

Every documentation claim must survive the following questions:

| Question | Required result |
|---|---|
| What source validates this claim? | OpenSIPS URL, RFC, or repository artifact. |
| What observation would falsify it? | A concrete failing condition. |
| Is the claim operationally testable? | Yes, through command, config check, network inspection, SQL query, or SIP/RTP flow. |
| Is the claim deterministic? | No subjective or ambiguous language. |
| Does it preserve Docker-first and PostgreSQL-only? | Must pass. |
| Does it expose Asterisk or PostgreSQL externally? | Must not. |

## 7. Canonical documentation fix workflow

```text
REQUEST
  |
  v
READ canonical docs
  |
  v
PARALLEL AGENT DISPATCH
  |-- doc-forensics
  |-- opensips-rfc-validator
  |-- solution-architecture
  |-- devops-docs
  |-- data-specs
  |-- implementation-specs
  |-- socratic-popper-reviewer
  |
  v
COLLECT FINDINGS
  |
  v
RECONCILE CONTRADICTIONS
  |
  v
APPLY DOCUMENTATION FIXES
  |
  v
FINAL CONFORMANCE GATE
```

## 8. Mandatory final report format

At the end of each documentation/specification task, the orchestrator must report:

```text
AGENT_ORCHESTRATION_STATUS:
  doc-forensics: pass|fail|not-run
  opensips-rfc-validator: pass|fail|not-run
  solution-architecture: pass|fail|not-run
  devops-docs: pass|fail|not-run
  data-specs: pass|fail|not-run
  implementation-specs: pass|fail|not-run
  socratic-popper-reviewer: pass|fail|not-run

CANONICAL_CONFORMANCE:
  docker_image_first: pass|fail
  postgresql_only: pass|fail
  opensips_lts_validated: pass|fail
  asterisk_private: pass|fail
  postgres_private: pass|fail
  rtp_masked: pass|fail

DOCUMENTATION_FIXES:
  - file:
    change:
    reason:

FALSIFICATION_STATUS:
  challenged_claims: <integer>
  resolved_claims: <integer>
  unresolved_claims: <integer>
  unresolved_blocking_claims: 0
```

## 9. Canonical enforcement rule

For every future user request that asks for documentation, specifications, architecture, DevOps documentation, implementation guidance, OpenSIPS configuration guidance, database schema guidance, or canonical project decisions, the orchestrator must execute this playbook before finalizing the answer.

If full agent execution is unavailable, the orchestrator must explicitly run the same role checks in-process and report `not-run` only for agents that could not be dispatched.

The final answer must not claim completion unless the completion gate in this playbook passes.
