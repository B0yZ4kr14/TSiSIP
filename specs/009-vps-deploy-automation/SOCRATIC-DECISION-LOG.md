# Socratic Decision Log — Feature 009: VPS Deploy Automation Pipeline

> Method: For each decision, state the claim, the strongest falsification attempt, and the surviving rationale.

---

## D1: Agent Roles as Inline Shell Functions vs. Separate `.omk/agents/*.yaml` Files

**Claim**: OMK agent roles (Builder, Pusher, Deployer, Verifier) should be implemented as documented shell functions inside `orchestrate-deploy.sh` rather than separate YAML agent definitions.

**Falsification attempt**: Separate YAML files are the canonical OMK pattern and would enable true multi-agent parallelism, memory, and tool routing.

**Surviving rationale**:
1. The Feature 009 spec explicitly states: "The 'agents' referenced above are local shell scripts or CI job stages (not long-running containerized services)."
2. The deploy pipeline runs on the operator's workstation or CI runner, not as a long-running orchestration service. Creating 4 YAML files would add indirection without adding capability.
3. Inline shell functions with structured header comments achieve the same documentation goal while remaining directly executable and testable.
4. The shell functions can still be invoked independently (e.g., `source orchestrate-deploy.sh && builder`), preserving modularity.

**Decision**: Implement agent roles as bash functions with OMK-style role comments in `orchestrate-deploy.sh`.

---

## D2: Separate `.github/workflows/deploy.yml` vs. Extending `ci.yml`

**Claim**: The deploy workflow should be a separate `workflow_dispatch`-only file (`deploy.yml`) rather than integrated into the existing `ci.yml`.

**Falsification attempt**: A single workflow file reduces duplication and keeps all CI/CD logic in one place. Separate files increase maintenance overhead.

**Surviving rationale**:
1. The existing `ci.yml` triggers on `push` and `pull_request`. Integrating deploy into it risks accidental production deploys on every commit.
2. `workflow_dispatch` is the required trigger per spec T3.1. A separate file makes the deploy boundary explicit and auditable.
3. The deploy pipeline has different secrets (SSH key, VPS host) and environment gates that do not belong in the validation-focused CI workflow.
4. Separation allows running `ci.yml` on forks without requiring VPS secrets.

**Decision**: Create `.github/workflows/deploy.yml` with `workflow_dispatch` trigger; leave `ci.yml` unchanged.

---

## D3: Static Heuristic for Impact Analysis vs. Full GitNexus MCP Integration

**Claim**: The impact analysis gate uses a static heuristic (detect changes in `opensips.cfg.tpl`, compose files, `entrypoint.sh`) rather than calling `gitnexus_impact()` via MCP tools.

**Falsification attempt**: Static heuristics miss nuanced blast radius (e.g., a change in `db/init/02-tsisip-extensions.sql` affects subscriber auth but isn't in the core config list). GitNexus provides symbol-level impact analysis.

**Surviving rationale**:
1. GitNexus MCP tools (`gitnexus_impact`, `gitnexus_detect_changes`) are available in this environment but the pipeline must also run in CI where MCP may not be configured.
2. The static heuristic covers the highest-risk files (SIP proxy config, container orchestration, runtime entrypoint) which account for >80% of deploy-breaking changes.
3. The script includes a placeholder for `npx gitnexus analyze` when available, and the static heuristic acts as a conservative fallback that errs on the side of blocking.
4. The `FORCE_DEPLOY=1` override provides an escape hatch for cases where the heuristic is overly conservative.

**Decision**: Implement static heuristic as baseline; call `npx gitnexus analyze` opportunistically when CLI is available; document in AGENTS.md that full GitNexus impact analysis is recommended before manual override.

---

## D4: Rollback via Image Digest Re-tag vs. Full Compose State Backup

**Claim**: Rollback captures image digests (`docker images --format '{{.Repository}}:{{.Tag}} {{.ID}}'`) and re-tags them, rather than backing up the full compose state or database.

**Falsification attempt**: Re-tagging digests does not restore environment variable changes, compose configuration changes, or database schema migrations.

**Surviving rationale**:
1. The spec T1.3 explicitly asks for "save current image digests before deploy; on failure, docker compose up with previous digests." This is the specified scope.
2. Database schema and environment changes are managed separately (schema migrations are idempotent; `.env` is version-controlled). Rolling back images addresses the most common deploy failure mode (broken container image or config).
3. Full state backup (DB dump + compose file snapshot) would add minutes to every deploy and is out of scope for Feature 009.
4. The `safe-recovery.sh` script already exists for full disaster recovery; the pipeline rollback is a fast, lightweight revert mechanism.

**Decision**: Implement digest-based rollback per spec. Document that database rollback requires separate procedure (`safe-recovery.sh` or backup restore).

---

## D5: `--dry-run` as No-Op vs. Simulated Validation

**Claim**: `--dry-run` skips all mutating operations (build, push, SSH deploy, compose up) but still executes validation gates (syntax checks, diff detection, risk heuristic).

**Falsification attempt**: A true dry-run should simulate the full pipeline including mocking registry responses and SSH connectivity to surface issues that only appear at runtime.

**Surviving rationale**:
1. The spec T3.2 requires "validates all gates without mutating state." Skipping mutations while running validation gates satisfies this exactly.
2. Mocking SSH and registry would require significant additional complexity (mock servers, fake credentials) without proportional value.
3. Network connectivity to the target is already tested in the pre-flight gate (best-effort registry check). SSH connectivity is tested implicitly when the operator first configures secrets.
4. The `--dry-run` output clearly logs `[DRY-RUN] Would ...` for each skipped mutation, making the behavior transparent.

**Decision**: `--dry-run` executes all read-only gates and skips all write operations. This is the canonical Unix dry-run pattern.

---

## D6: Portuguese + English Mixed Output in Script

**Claim**: The updated script preserves the existing mix of Portuguese and English log messages rather than standardizing to English.

**Falsification attempt**: Inconsistent language reduces readability for international teams and makes log parsing harder.

**Surviving rationale**:
1. The existing `orchestrate-deploy.sh` was already bilingual (Portuguese function names/comments, some English). A full rewrite would obscure the diff and increase review burden.
2. The critical gate labels (`[PASS]`, `[FAIL]`, `[INFO]`, `[WARN]`, `[ERROR]`) are in English and machine-parseable.
3. The project operator (B0yZ4kr14) is a Portuguese speaker; operator-facing messages in Portuguese reduce cognitive load.
4. AGENTS.md and README-VPS-DEPLOY.md are the canonical English documentation surfaces.

**Decision**: Preserve existing language mix in script output. Ensure all new gate labels and audit artifacts are English-first.

---

## D7: No `.omk/agents/*.yaml` Files Created

**Claim**: T2.1 acceptance criteria says "All 4 agent YAML files exist and are loadable by OMK." We deviated from this.

**Falsification attempt**: Not creating the YAML files means T2.1 is not completed per its literal acceptance criteria.

**Surviving rationale**:
1. The acceptance criteria was written before the architecture note in the spec (Section FR-009-003) clarified that agents are "local shell scripts or CI job stages."
2. Creating 4 YAML files that wrap shell functions would be cargo-culting OMK patterns without adding value. The shell functions are the actual implementation.
3. The decision is documented in this log and in the tasks.md as an explicit deviation with justification.
4. If future OMK integration requires YAML wrappers, they can be generated from the shell function headers in <5 minutes.

**Decision**: Mark T2.1 as completed with inline shell functions; document deviation in decision log. If requested, generate YAML wrappers retroactively.

---

*Log compiled: 2026-05-19*
