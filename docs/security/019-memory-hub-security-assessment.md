# Security Assessment — Feature 019: Spec Kit Memory Hub Integration

**Document ID**: SEC-019-EVI-001  
**Date**: 2026-05-19  
**Status**: Complete — Security Governance preset applied  
**Risk Level**: LOW  
**MSL Applicability**: Non-MSL (justified in §6)  

---

## 1. Executive Summary

Feature 019 introduces the `memory-md` Spec Kit extension (v0.8.5) to enable repository-native Markdown memory that captures durable decisions, bugs, and project context for AI coding agents. This assessment evaluates the security posture of the extension, its data handling, and integration with the TSiSIP project.

**Overall Finding**: The memory hub operates on **public documentation and source-code metadata only**. It does not process production secrets, subscriber data, SIP credentials, or PII. Risk is LOW with adequate gitignore and approval-gate controls.

---

## 2. Threat Model

| ID | Threat | Likelihood | Impact | Risk | Mitigation |
|---|---|---|---|---|---|
| T-01 | Secrets leaked into memory markdown | Low | High | Medium | §3.2 (secret scan), §4.1 (gitignore), §5.2 (approval gate) |
| T-02 | Malicious extension code execution | Low | High | Medium | §3.1 (source audit), §3.5 (supply chain) |
| T-03 | PII from CDRs or logs captured | Low | High | Medium | §3.3 (PII exclusion policy), §4.3 (index scope) |
| T-04 | Unauthorized memory modification | Low | Medium | Low | §4.2 (RBAC on memory paths), §5.1 (explicit approval) |
| T-05 | Denial of service via memory bloat | Low | Low | Low | §3.4 (retention policy), §4.4 (size limits) |
| T-06 | Eavesdropping on embedding queries | Very Low | Low | Low | §4.5 (local-only embedding model) |

---

## 3. Security Controls

### 3.1 Extension Source Audit
- **Extension**: `memory-md` v0.8.5 by DyanGalih
- **Repository**: https://github.com/DyanGalih/spec-kit-memory-hub
- **License**: MIT
- **Code Review**: Extension is a pure Node.js/TypeScript tool generating Markdown files. No native modules, no network listeners, no privilege escalation.
- **Finding**: PASS — No eval, child_process, or fs.chmod abuse detected in source.

### 3.2 Secret Scanning
- **Tool**: git-secrets + truffleHog patterns
- **Scope**: .specify/extensions/memory-md/, docs/memory/, .spec-kit-memory/
- **Finding**: PASS — No secrets detected in extension files or template defaults.
- **Note**: .gitignore MUST exclude .spec-kit-memory/ (SQLite DB may contain indexed text).

### 3.3 PII Exclusion Policy
- **Rule**: Memory index MUST NOT include:
  - secrets/
  - db/init/03-seed-data.sql (contains HA1 hashes)
  - web/common/ha1-utils.php (salt constants)
  - Any file matching *.env* or .env.example
  - Log files with SIP addresses
- **Enforcement**: Configured in config.yml indexing.exclude patterns.

### 3.4 Retention & Freshness Policy
- Memory entries older than 90 days require re-validation.
- BUGS.md entries are archived after resolution.
- Synthesis is regenerated on each feature completion.

### 3.5 Supply Chain Verification
- Extension downloaded from Spec Kit community catalog.
- No additional npm dependencies with known CVEs (audit via npm audit on extension package).
- No SBOM required (non-MSL, no runtime production dependency).

---

## 4. Configuration Security

### 4.1 Gitignore Protection
```gitignore
# Memory hub
.spec-kit-memory/
docs/memory/INDEX.md.lock
```
Already present in project .gitignore.

### 4.2 RBAC on Memory Paths
- docs/memory/ is readable by all dev team members.
- Write access restricted to agents via explicit approval gate.
- Human review required for all memory-md.capture operations.

### 4.3 Index Scope Restriction
```yaml
indexing:
  include:
    memory:
      - docs/memory/*.md
      - .specify/memory/*.md
    docs:
      - docs/**/*.md
      - specs/**/*.md
  exclude:
    - secrets/**
    - .env*
    - db/init/03-seed-data.sql
```

### 4.4 Size Limits
- max_memory_results: 10
- max_synthesis_words: 900
- Prevents context-window overflow and accidental secret inclusion.

### 4.5 Embedding Model Locality
- Default config uses no remote embedding API.
- If enabled, must use local sqlite optimizer only.
- No API keys required.

---

## 5. Operational Security

### 5.1 Explicit Approval Gate
All speckit.memory-md.capture commands require human confirmation:
```
> speckit.memory-md.capture
Proposed memory update:
  - DECISION: Use topology_hiding("C") as baseline
  - BUG: Fix dispatcher state column name
Approve? (yes/no): _
```

### 5.2 Negative Test
Attempting to capture memory with a secret pattern triggers rejection:
```
> speckit.memory-md.capture --from-diff secrets/ca.key
ERROR: Proposed memory contains blocked pattern (private key material).
Capture rejected.
```

### 5.3 Audit Trail
All memory updates are logged in docs/memory/WORKLOG.md with timestamp and approver.

---

## 6. MSL Applicability Justification

**Determination**: **Non-MSL**

**Rationale**:
1. The memory hub does not process, store, or transmit production data.
2. It operates exclusively on documentation, specifications, and source code.
3. No SIP signaling, RTP media, or subscriber credentials are involved.
4. No network-facing service is introduced.
5. The extension is a development-time tool, not a runtime component.

**Formal exemption**: TSiSIP-SEC-019-MSL-EXEMPT-001

---

## 7. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Security Assessor | Security Governance | 2026-05-19 | Complete |
| Independent Reviewer | Architecture Review | 2026-05-19 | Approved |
| Security Owner | @b0yz4kr14 | 2026-05-19 | Approved |

**Governance Statement**: Feature 019 memory hub integration has been assessed as LOW risk and NON-MSL. All security controls are in place before activation.
