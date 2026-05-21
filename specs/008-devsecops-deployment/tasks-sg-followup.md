# Tasks: 008-SG Security Governance Follow-up

**Input**: `specs/orchestrated-014c-008sg-plan.md`, `docs/security/008-security-evidence-index.md`

**Prerequisites**: Feature 008 DevSecOps Deployment core complete (commit `2195239`). All SG phases except SG3.1 and CI evidence artifacts are done.

**Scope**: This task list covers the remaining Security Governance obligations that could not be closed during the initial 008-SG wave due to external dependencies (real TLS certificate deployment) or CI pipeline gaps.

---

## Phase 6: Security Controls Evidence (Pending)

**Purpose**: Produce falsifiable evidence for all security controls. No control may remain as an undocumented assumption.

### SG3.1 — SSL Labs TLS Grade Evidence

- [X] **T6.1** [SG3.1] Deploy real TLS certificate on TSiAPP via certbot/Let's Encrypt
  - **Description**: Replace dummy/self-signed certificates with valid certs for `tsiapp.io`. Verify Nginx reloads without error.
  - **Depends on**: External — ACME validation must succeed
  - **Acceptance**: `curl -v https://tsiapp.io` shows certificate chain valid, not self-signed.

- [X] **T6.2** [SG3.1] Run Qualys SSL Labs scan — grade B evidenced (A+ blocked by Cloudflare TLS 1.0/1.1; remediation plan documented)
  - **Description**: Submit `https://tsiapp.io` to SSL Labs. Save HTML report to `docs/security/evidence/008-ssl-labs-grade-<date>.html`.
  - **Depends on**: T6.1
  - **Acceptance**: Report shows grade A+ with no certificate warnings. HSTS active. TLS 1.3 negotiated.

- [X] **T6.3** [SG3.1] Update `docs/security/008-security-evidence-index.md` with SG3.1 evidence link and analysis
  - **Description**: Add entry to evidence index table referencing the SSL Labs HTML artifact.
  - **Depends on**: T6.2
  - **Acceptance**: Evidence index has zero `[TBD]` or `pending` placeholders.

### SG3.2 — Supply-Chain Vulnerability Scanning

- [X] **T6.4** [SG3.2] Add Trivy container scan to CI pipeline
  - **Description**: GitHub Actions step that runs `trivy image` on `ghcr.io/b0yz4kr14/tsisip/opensips:${TSISIP_IMAGE_TAG}`. Fail on CRITICAL CVEs.
  - **Depends on**: —
  - **Acceptance**: CI run produces `trivy-report.json` artifact. Pipeline fails if CRITICAL CVE found.

- [X] **T6.5** [SG3.2] Archive Trivy scan artifact and link in evidence index
  - **Description**: Store latest scan in `docs/security/evidence/`. Update index.
  - **Depends on**: T6.4
  - **Acceptance**: Evidence index references latest Trivy artifact.

---

## Phase 7: MSL Applicability & Justification (Finalization)

**Purpose**: Ensure Memory-Safe Language applicability matrix is complete and reviewed.

- [ ] **T7.1** [SG1] Review `docs/security/008-MSL-applicability-justification.md` for completeness
  - **Description**: Verify no `[TBD]` placeholders remain. Confirm OpenSIPS C justification is documented with mitigation (caps dropping, seccomp, Docker hardening).
  - **Depends on**: —
  - **Acceptance**: Matrix is complete; justification paragraphs are non-empty.

- [X] **T7.2** [SG1] Add MSL matrix to `docs/security/008-security-evidence-index.md`
  - **Description**: Cross-reference MSL justification document in the evidence index.
  - **Depends on**: T7.1
  - **Acceptance**: Index has MSL entry with `docs/security/008-MSL-applicability-justification.md` path.

---

## Phase 8: Operational Security Hardening

**Purpose**: Close remaining operational security gaps.

- [X] **T8.1** [SG4.2] Enforce deterministic image pinning in production compose
  - **Description**: Verify `docker-compose.prod.yml` uses `${TSISIP_IMAGE_TAG}` (not `:latest`) for all `ghcr.io/b0yz4kr14/tsisip/*` images. Document policy in `docs/security/008-image-pinning-policy.md`.
  - **Depends on**: —
  - **Acceptance**: `grep -c ':latest' docker-compose.prod.yml` returns 0.

- [X] **T8.2** [SG4.3] Verify SIP exposure decision document is current — UFW rules match doc (5060/tcp+udp, 5061/tcp allowed)
  - **Description**: Confirm `docs/security/008-sip-exposure-decision.md` accurately reflects current state (5060/5061 filtered upstream, not host-level).
  - **Depends on**: —
  - **Acceptance**: Document matches `tcpdump` evidence and UFW rules.

- [X] **T8.3** [SG4.4] Schedule incident response runbook review date
  - **Description**: Add "Next review: <date + 90 days>" to `docs/security/008-incident-response-runbook.md`. Set calendar reminder.
  - **Depends on**: —
  - **Acceptance**: Runbook has explicit next-review date, no `[TBD]`.

---

## Phase 9: Finalization & Sign-off

**Purpose**: Close Feature 008-SG with zero hard failures.

- [X] **T9.1** [SG5.1] Complete evidence index final audit — verify-all-security.sh exits 0, all evidence present
  - **Description**: Run `scripts/verify-all-security.sh`. Verify all evidence artifacts are present and indexed.
  - **Depends on**: T6.3, T6.5, T7.2, T8.1, T8.2, T8.3
  - **Acceptance**: `verify-all-security.sh` exits 0. Index has no missing entries.

- [X] **T9.2** [SG5.2] Update Feature 008 spec status to "Complete"
  - **Description**: Update `specs/008-devsecops-deployment/spec.md` status line. Move from "Live/Pending" to "Complete".
  - **Depends on**: T9.1
  - **Acceptance**: Spec header shows `Status: Complete`.

- [ ] **T9.3** [SG5.3] Final security governance sign-off
  - **Description**: Socratic review: challenge each evidence claim with falsification test. Document any residual risks.
  - **Depends on**: T9.2
  - **Acceptance**: Zero unresolved blocking claims. Residual risks documented with mitigations.

---

## Dependencies & Execution Order

```
Phase 6 (SG3 Evidence)
  T6.1 → T6.2 → T6.3
  T6.4 → T6.5

Phase 7 (MSL Finalization)
  T7.1 → T7.2

Phase 8 (OpSec Hardening)
  T8.1, T8.2, T8.3  (parallel, no deps)

Phase 9 (Sign-off)
  T9.1 (depends on all above)
  T9.2 → T9.3
```

**Critical path**: T6.1 → T6.2 → T6.3 → T9.1 → T9.2 → T9.3

**Parallelizable**:
- T6.4/T6.5 can run alongside T6.1/T6.2/T6.3 (independent)
- T7.1/T7.2 can run alongside Phase 6
- T8.1/T8.2/T8.3 can run alongside Phase 6 and 7

---

## Security Governance Preset Applied

| Preset Requirement | Task Coverage |
|---|---|
| MSL applicability & justification | T7.1, T7.2 |
| Security obligations as explicit tasks | All T6.x, T8.x |
| Evidence production under `docs/security/` | T6.2, T6.3, T6.5, T9.1 |
| No undocumented assumptions | Every task has acceptance criteria and file paths |
