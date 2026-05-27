# Feature 033 Tasks: Performance Benchmarking & Load Testing Framework

**Last Updated**: 2026-05-27

---

## Phase 1: SIP Benchmark

### T001: Create benchmark-sip.sh
**Description**: Create `scripts/benchmark-sip.sh` that uses `sipsak` to flood REGISTER and INVITE requests to OpenSIPS, measuring requests/second and success rate.
**Files affected**: `scripts/benchmark-sip.sh`
**Depends on**: —
**Status**: [x]

### T002: Add OpenSIPS health check before benchmark
**Description**: Verify OpenSIPS is healthy before running benchmarks to avoid false negatives.
**Files affected**: `scripts/benchmark-sip.sh`
**Depends on**: T001
**Status**: [x]

---

## Phase 2: PostgreSQL Benchmark

### T003: Create benchmark-pgsql.sh
**Description**: Create `scripts/benchmark-pgsql.sh` that measures subscriber lookup query latency using a timed loop against PostgreSQL.
**Files affected**: `scripts/benchmark-pgsql.sh`
**Depends on**: —
**Status**: [x]

---

## Phase 3: Reporting

### T004: Create benchmark-report.sh
**Description**: Create `scripts/benchmark-report.sh` that runs all benchmarks and generates a markdown report with timestamps and results.
**Files affected**: `scripts/benchmark-report.sh`
**Depends on**: T001, T003
**Status**: [x]

### T005: Add historical comparison
**Description**: Store previous benchmark results and compare current run against baseline, flagging regressions > 10%.
**Files affected**: `scripts/benchmark-report.sh`
**Depends on**: T004
**Status**: [x]

---

## Phase 4: CI Integration

### T006: Add benchmark step to CI workflow
**Description**: Add a benchmark job to `.github/workflows/ci.yml` that runs on PRs and reports regressions.
**Files affected**: `.github/workflows/ci.yml`
**Depends on**: T004
**Status**: [x]

---

## Phase 5: Tests & Documentation

### T007: Create integration tests
**Description**: Create `tests/integration/test_benchmarks.py` validating that benchmark scripts exist and produce valid output.
**Files affected**: `tests/integration/test_benchmarks.py`
**Depends on**: T004
**Status**: [x]

### T008: Update operator runbook
**Description**: Add benchmark procedures to `docs/TSiSIP-OPERATOR-RUNBOOK.md`.
**Files affected**: `docs/TSiSIP-OPERATOR-RUNBOOK.md`
**Depends on**: T004
**Status**: [x]

### T009: Update CHANGELOG
**Description**: Add Feature 033 entry to `docs/CHANGELOG-2026-05.md`.
**Files affected**: `docs/CHANGELOG-2026-05.md`
**Depends on**: T007
**Status**: [x]
