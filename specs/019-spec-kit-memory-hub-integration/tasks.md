# Feature 019 Tasks



## Phase 0: Setup & Extension Installation
- [x] T001: Verify specify-cli version supports memory-hub extension (v0.8.12.dev0+)
- [x] T002: Install memory-md extension via specify-cli
- [x] T003: Update .specify/extensions.yml installed list with memory-md
- [x] T004: Create .specify/extensions/memory-md/ directory structure
- [x] T005: Run speckit-utils.doctor to validate post-install health

## Phase 1: Security Governance & Evidence
- [x] T006: Create docs/security/019-memory-hub-security-assessment.md — data classification, secret prohibition, access control, retention policy
- [x] T007: Create docs/security/019-agent-memory-governance.md — allowed/banned content, capture approval workflow, role boundaries
- [x] T008: Update docs/security/008-security-evidence-index.md with Feature 019 entries and expiration dates
- [x] T009: MSL applicability review — assess if memory-hub falls under Minimum Security Level; document justification
- [x] T010: Scan existing .specify/memory/*.md for accidental secret leakage (grep patterns: password=, secret=, api_key, private_key, BEGIN RSA)

## Phase 2: Configuration & Bootstrap
- [x] T011: Create .specify/extensions/memory-md/config.yml with optimizer.enabled: false (local-only)
- [x] T012: Configure index path (project-local inside .specify/extensions/memory-md/)
- [x] T013: Configure optimizer chunking strategy for markdown files
- [x] T014: Bootstrap index from existing .specify/memory/*.md files
- [x] T015: Verify indexed content contains zero secrets or PII (automated grep scan)

## Phase 3: Integration & Validation
- [x] T016: Test speckit.memory-md.prepare-context --feature specs/019-spec-kit-memory-hub-integration
- [x] T017: Test speckit.memory-md.capture with a non-sensitive architectural decision
- [x] T018: Negative test — verify explicit approval gate blocks auto-capture without approval
- [x] T019: Test cross-file query — synthesize answer from constitution.md + architecture_constitution.md
- [x] T020: End-to-end validation — run full prepare-context → capture → query cycle

## Phase 4: Documentation & Closure
- [x] T021: Update AGENTS.md with memory-hub usage guidance for future agents
- [x] T022: Create docs/TSiSIP-MEMORY-HUB-RUNBOOK.md with memory-hub operational procedures
- [x] T023: Create specs/019-spec-kit-memory-hub-integration/blueprint.md
- [x] T024: Stage all changes and write conventional commit
- [x] T025: Push to GitHub and close OMK Goal

## Security Review Checkpoints

| Checkpoint | Trigger | Gate Condition |
|---|---|---|
| SR-1 | After T1.3 | Governance artifacts must explicitly prohibit secrets and define access control |
| SR-2 | After T2.5 | Index must pass automated secret-leakage scan with zero findings |
| SR-3 | After T3.3 | Approval gate must block unauthorized capture in negative test |

## Dependency Graph

```
W0 (Setup) → W1 (Security) → W2 (Config)
                              ↓
                         W3 (Validation) → W4 (Docs/Git)
```
