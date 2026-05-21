# Feature 019 Tasks

## Wave 0: Setup & Extension Installation
- [x] T0.1: Verify specify-cli version supports memory-hub extension (v0.8.12.dev0+)
- [x] T0.2: Install spec-kit-memory-hub extension via specify-cli
- [x] T0.3: Update .specify/extensions.yml installed list with spec-kit-memory-hub
- [x] T0.4: Create .specify/extensions/memory-md/ directory structure
- [x] T0.5: Run speckit-utils.doctor to validate post-install health

## Wave 1: Security Governance & Evidence
- [x] T1.1: Create docs/security/019-memory-hub-security-assessment.md — data classification, secret prohibition, access control, retention policy
- [x] T1.2: Create docs/security/019-agent-memory-governance.md — allowed/banned content, capture approval workflow, role boundaries
- [x] T1.3: Update docs/security/008-security-evidence-index.md with Feature 019 entries and expiration dates
- [x] T1.4: MSL applicability review — assess if memory-hub falls under Minimum Security Level; document justification
- [x] T1.5: Scan existing .specify/memory/*.md for accidental secret leakage (grep patterns: password=, secret=, api_key, private_key, BEGIN RSA)

## Wave 2: Configuration & Bootstrap
- [x] T2.1: Create .specify/extensions/memory-md/config.yml with optimizer.enabled: false (local-only)
- [x] T2.2: Configure index path (project-local inside .specify/extensions/memory-md/)
- [x] T2.3: Configure embedding model and markdown chunking strategy
- [x] T2.4: Bootstrap index from existing .specify/memory/*.md files
- [x] T2.5: Verify indexed content contains zero secrets or PII (automated grep scan)

## Wave 3: Integration & Validation
- [x] T3.1: Test speckit.memory-md.prepare-context --feature specs/019-spec-kit-memory-hub-integration
- [x] T3.2: Test speckit.memory-md.capture with a non-sensitive architectural decision
- [x] T3.3: Negative test — verify explicit approval gate blocks auto-capture without approval
- [x] T3.4: Test cross-file query — synthesize answer from constitution.md + architecture_constitution.md
- [x] T3.5: End-to-end validation — run full prepare-context → capture → query cycle

## Wave 4: Documentation & Closure
- [x] T4.1: Update AGENTS.md with memory-hub usage guidance for future agents
- [x] T4.2: Create docs/TSiSIP-MEMORY-HUB-RUNBOOK.md with memory-hub operational procedures
- [x] T4.3: Create specs/019-spec-kit-memory-hub-integration/blueprint.md
- [x] T4.4: Stage all changes and write conventional commit
- [x] T4.5: Push to GitHub and close OMK Goal

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
