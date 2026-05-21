# Feature 019 Implementation Plan

## Wave 0: Setup & Extension Installation

**Agent:** `devops-docs`

- [ ] W0.1: Verify specify-cli version supports memory-hub extension (v0.8.12.dev0+)
- [ ] W0.2: Install spec-kit-memory-hub extension via specify-cli
- [ ] W0.3: Update .specify/extensions.yml installed list with memory-hub
- [ ] W0.4: Create .specify/extensions/memory-md/ directory structure
- [ ] W0.5: Run speckit-utils.doctor to validate post-install health

## Wave 1: Security Governance & Evidence

**Agents:** `security`, `doc-forensics`

**Security Review Checkpoint 1** (after W1.3): Verify governance artifacts cover data classification, secret prohibition, and access control.

- [ ] W1.1: Create docs/security/019-memory-hub-security-assessment.md
  - Data classification for memory entries (public, internal, restricted)
  - Secret/credential/PII prohibition
  - Access control model (devops+ for synthesis, admin for capture approval)
  - Retention and purge policy
- [ ] W1.2: Create docs/security/019-agent-memory-governance.md
  - Explicit ban on storing: secrets, private keys, runtime credentials, tenant PII, CDR contents
  - Allowed content: architecture decisions, patterns, lessons learned, constraint changes
  - Capture approval workflow
- [ ] W1.3: Update docs/security/008-security-evidence-index.md with Feature 019 entries
- [ ] W1.4: MSL Applicability Review — determine if memory-hub falls under Minimum Security Level
  - If MSL-applicable: document controls
  - If non-MSL: document justification with risk acceptance
- [ ] W1.5: Secure-development verification — scan existing .specify/memory/*.md for accidental secret leakage

## Wave 2: Configuration & Bootstrap

**Agent:** `coder`

**Security Review Checkpoint 2** (after W2.5): Validate no secrets, credentials, or PII exist in the bootstrapped index.

- [ ] W2.1: Create .specify/extensions/memory-md/config.yml with optimizer.enabled: true
- [ ] W2.2: Configure index path (project-local inside .specify/extensions/memory-md/)
- [ ] W2.3: Configure embedding model and chunking strategy for markdown files
- [ ] W2.4: Bootstrap index from existing .specify/memory/*.md files
- [ ] W2.5: Verify all indexed content is non-sensitive (automated grep scan for patterns: password=, secret=, api_key, private_key)

## Wave 3: Integration & Validation

**Agent:** `coder`

**Security Review Checkpoint 3** (after W3.3): Confirm approval gate prevents unauthorized capture.

- [ ] W3.1: Test speckit.memory-md.prepare-context --feature specs/019-spec-kit-memory-hub-integration
- [ ] W3.2: Test speckit.memory-md.capture with a non-sensitive architectural decision
- [ ] W3.3: Verify explicit approval gate blocks auto-capture (negative test)
- [ ] W3.4: Test cross-file query — ask a question that requires synthesizing constitution.md + architecture_constitution.md
- [ ] W3.5: End-to-end validation — run prepare-context → capture → query cycle

## Wave 4: Documentation & Closure

**Agents:** `docs`, `release`

- [ ] W4.1: Update AGENTS.md with memory-hub usage guidance for future agents
- [ ] W4.2: Update docs/TSiSIP-OPERATOR-RUNBOOK.md with memory-hub operational procedures
- [ ] W4.3: Create blueprint.md for Feature 019
- [ ] W4.4: Conventional commit all changes
- [ ] W4.5: Push to GitHub and close OMK Goal

## Dependency Graph

```
W0 (Setup) → W1 (Security) → W2 (Config)
                              ↓
                         W3 (Validation) → W4 (Docs/Git)
```

## Security Review Checkpoints

| Checkpoint | Trigger | Gate |
|---|---|---|
| SR-1 | After W1.3 | Governance artifacts must cover secret prohibition and access control |
| SR-2 | After W2.5 | Index must contain zero secrets or PII |
| SR-3 | After W3.3 | Approval gate must block unauthorized capture |

## Supply-Chain Evidence Updates

- Update docs/security/008-supply-chain-status.md to list memory-hub extension origin (github-spec-kit) and version.
- No new Docker images or base images introduced.

## MSL Applicability

| Aspect | Assessment |
|---|---|
| Memory-hub stores decisions, not user data | Likely **non-MSL** |
| If memory-hub is compromised, attacker learns architecture patterns | Low business impact |
| Mitigation: no secrets, no PII, project-local index | Justification for non-MSL |
| **Action**: Document non-MSL justification in W1.4 | Required |
