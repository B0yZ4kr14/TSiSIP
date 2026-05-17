# Research: OpenSIPS Docker Edge Proxy Foundation

## Decisions & Rationale

### Source Build vs APT Packages
**Decision**: Build OpenSIPS 3.6 from GitHub source (debian:bookworm-slim base).

**Rationale**:
- APT packages from apt.opensips.org caused config validation failures.
- The core binary had transport protocols linked in but opensips -c still required explicit loadmodule directives.
- Source build with include_modules resolved this by producing actual .so files.
- Full control over module set and compile flags.

**Alternatives considered**:
- APT install + manual symlinks (rejected: fragile, not reproducible).
- Alpine Linux base (rejected: libpq compatibility concerns, musl libc differences).

---

### Debian Bookworm Slim vs Other Bases
**Decision**: debian:bookworm-slim for both builder and runtime stages.

**Rationale**:
- Widely supported base with predictable libpq5, libssl3 packages.
- Build toolchain (gcc, make, bison, flex) readily available.
- Acceptable tradeoff for SIP ecosystem compatibility.

**Alternatives considered**:
- ubuntu:22.04 (rejected: larger image, no material benefit).
- alpine:3.19 (rejected: musl libc potential issues with db_postgres and libssl).

---

### HA1-Only Credential Storage
**Decision**: Store SIP Digest credentials as precomputed HA1 hashes (calculate_ha1=0).

**Rationale**:
- Never stores plaintext passwords in the database.
- OpenSIPS auth_db module reads HA1 columns directly.
- Supports MD5, SHA-256, and SHA-512/256 for future algorithm agility.

**Alternatives considered**:
- Plaintext subscriber.password (rejected: security violation).
- calculate_ha1=1 with plaintext storage (rejected: same issue).

---

### Topology Hiding Mode
**Decision**: topology_hiding("C") on all INVITE dialogs.

**Rationale**:
- "C" mode hides Contact headers in both directions.
- Prevents backend PBX IPs from leaking to external clients.
- Required for multi-tenant deployments where PBX backends must not be discoverable.

**Alternatives considered**:
- No topology hiding (rejected: exposes internal infrastructure).
- topology_hiding() without flags (rejected: "C" provides stronger concealment).

---

### Explicit RTPengine Functions
**Decision**: Use rtpengine_offer(), rtpengine_answer(), rtpengine_delete() explicitly.

**Rationale**:
- rtpengine_manage() obscures when SDP manipulation occurs.
- Explicit calls make the configuration auditable and deterministic.
- Aligns with TSiSIP canonical spec requirement.

**Alternatives considered**:
- rtpengine_manage() (rejected: violates project constitution).

---

### Fail-Fast DB Startup
**Decision**: No retry loop for PostgreSQL connectivity at startup.

**Rationale**:
- Container orchestrators have their own restart policies.
- Fail-fast provides clear, immediate feedback to operators.
- Avoids hidden delays during startup that complicate debugging.

**Alternatives considered**:
- Exponential backoff retry (rejected: defer to orchestrator).
- Infinite linear retry (rejected: masks infrastructure problems).

---

### Single-Instance Deployment
**Decision**: Foundation supports single OpenSIPS instance per deployment.

**Rationale**:
- Multi-instance requires shared dialog state.
- Adds significant complexity to the foundation feature.
- Dispatcher failover already provides backend PBX redundancy.
- Horizontal scaling is a distinct feature with its own design concerns.

**Alternatives considered**:
- Multi-instance with PostgreSQL-backed dialog state (rejected: exceeds foundation scope).
- Session affinity load balancing (rejected: requires external load balancer).

---

### RTPengine Container Strategy (Deferred)
**Decision**: Defer lightweight RTPengine container to unblock T4.4/T4.5.

**Rationale**:
- Debian rtpengine-daemon package pulls 146+ dependencies (~229 MB).
- Causes Docker build timeouts and potential daemon unresponsiveness.
- Purpose-built lightweight container or stub needed for CI/runtime testing.

**Resolution path**: Replace with source-built rtpengine or minimal stub container.
