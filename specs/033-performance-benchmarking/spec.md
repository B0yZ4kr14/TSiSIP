# Feature 033: Performance Benchmarking & Load Testing Framework

## Overview

| Field | Value |
|-------|-------|
| **Feature** | Performance Benchmarking & Load Testing Framework |
| **Short name** | performance-benchmarking |
| **Created** | 2026-05-27 |
| **Status** | In Progress |
| **Last Updated** | 2026-05-27 |
| **Context** | TSiSIP currently has no automated performance baseline. Operators cannot measure how many concurrent calls, registrations, or INVITEs per second the platform can handle before degradation. This feature adds repeatable load tests and benchmarking scripts. |
| **Objective** | Provide automated, repeatable performance benchmarks for OpenSIPS signaling capacity, PostgreSQL query throughput, and end-to-end call flow latency. |

## Goals

1. **SIP Registration Flood Test**: Measure max sustainable REGISTER requests/second
2. **INVITE Throughput Test**: Measure max sustainable INVITE transactions/second
3. **PostgreSQL Benchmark**: Measure subscriber lookup query latency under load
4. **End-to-End Call Latency**: Measure PDD (Post-Dial Delay) from INVITE to 180 Ringing
5. **Baseline Reporting**: Generate markdown benchmark reports with historical comparison

## Non-Goals

- Real user traffic simulation (use external tools like sipp for that)
- Media/RTP throughput testing (RTPengine handles this separately)
- Continuous load testing in production (benchmarks run manually or in CI)

## Acceptance Criteria

- [ ] **AC1**: `scripts/benchmark-sip.sh` runs registration flood and reports RPS capacity
- [ ] **AC2**: `scripts/benchmark-pgsql.sh` measures subscriber lookup latency
- [ ] **AC3**: `scripts/benchmark-report.sh` generates markdown report with timestamp
- [ ] **AC4**: All benchmarks run in Docker containers (no host dependencies)
- [ ] **AC5**: CI runs benchmarks on PR and reports regressions > 10%
