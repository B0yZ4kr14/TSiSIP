# Plan: Performance Benchmarking & Load Testing Framework

## Architecture

- **SIP Benchmark**: `scripts/benchmark-sip.sh` using `sipsak` for REGISTER/INVITE flooding
- **PostgreSQL Benchmark**: `scripts/benchmark-pgsql.sh` using `pgbench` or custom query loop
- **Report Generator**: `scripts/benchmark-report.sh` aggregating results into markdown
- **CI Integration**: GitHub Actions step running benchmarks and checking regressions

## Files

- `scripts/benchmark-sip.sh` — SIP signaling benchmarks
- `scripts/benchmark-pgsql.sh` — Database query benchmarks
- `scripts/benchmark-report.sh` — Report aggregation
- `docker/benchmark/Dockerfile` — Optional benchmark runner container
- `tests/integration/test_benchmarks.py` — Validation tests
