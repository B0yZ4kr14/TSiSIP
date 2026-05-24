# Blueprint: 024 — Brownfield Remediation

**Feature**: 024 — Brownfield Remediation  
**Branch**: `main` | **Date**: 2026-05-24  
**Mode**: doc-only  
**Total Tasks**: 34 | **Files**: 3 new, 13 modified, 0 deleted

---

## Key Decisions

- **SHA-pin the php:8.2-apache base image** to a specific digest for supply-chain determinism → T1.1, T1.2
- **Use `TEST_IP` environment variable** with `docker network inspect` fallback for all SIP test scripts → T2.1–T2.5
- **Dynamic IP discovery via `docker network inspect`** with strict fail-on-error (no fallback defaults) for deploy scripts → T3.1–T3.7
- **Inline comments before all `sleep` statements** explaining the wait purpose → T3.8–T3.10
- **Add missing `SUBSCRIBER_*_RATE_LIMIT` variables** to `.env.example` with descriptive placeholders → T4.1–T4.4
- **OCP container healthcheck uses `127.0.0.1`** which works inside the container namespace regardless of host `userland-proxy` setting → T5.1, T5.2
- **HEALTHCHECK instructions follow AD-024-3**: lightweight command, 30s interval, 3 retries, appropriate `start_period` → T5.3–T5.8

---

## Implementation Order

```
Phase 1 (T1.1–T1.5) ──┐
Phase 2 (T2.1–T2.5) ──┤
Phase 3 (T3.1–T3.11) ─┤──> Phase 6 (T6.1–T6.6)
Phase 4 (T4.1–T4.4) ──┤
Phase 5 (T5.1–T5.8) ──┘
         │
    SR-1 / SR-2 / SR-3
```

Phases 1–5 can execute in parallel. Phase 6 (validation) requires all prior phases complete.

---

## Phase 1: Supply-Chain Determinism (B1)

### T1.1: Replace FROM line in docker/admin-api/Dockerfile with SHA-pinned php image

**File**: `docker/admin-api/Dockerfile` (modify)

**Requirements**: AC1

**Dependencies**: None

**Before** (line 4):

```dockerfile
FROM php:8.2-apache
```

**After** (line 4):

```dockerfile
FROM php:8.2-apache@sha256:4e65fefba73c49d80ff4b6f2c03e691b7437e9f1c315f5b4f5f2f3e6a7b8c9d0
```

> **Note**: The SHA256 digest above is a placeholder. The actual digest must be fetched from Docker Hub at implementation time:
> ```bash
> docker pull php:8.2-apache
> docker inspect php:8.2-apache --format='{{index .RepoDigests 0}}'
> ```

**Verification**: `docker build -t tsisip/admin-api:test docker/admin-api/` succeeds without errors.

---

### T1.2: Add digest comment documenting fetch date and SHA verification command

**File**: `docker/admin-api/Dockerfile` (modify)

**Requirements**: AC1

**Dependencies**: T1.1

**Before** (line 4):

```dockerfile
FROM php:8.2-apache@sha256:4e65fefba73c49d80ff4b6f2c03e691b7437e9f1c315f5b4f5f2f3e6a7b8c9d0
```

**After** (lines 4–6):

```dockerfile
# Base image pinned to SHA256 digest for supply-chain determinism.
# Fetched: 2026-05-24
# Verify: docker inspect php:8.2-apache --format='{{index .RepoDigests 0}}'
FROM php:8.2-apache@sha256:4e65fefba73c49d80ff4b6f2c03e691b7437e9f1c315f5b4f5f2f3e6a7b8c9d0
```

**Verification**: `docker build` succeeds; Dockerfile contains comment with verification command.

---

### T1.3: Run docker build to verify image builds successfully

**File**: `docker/admin-api/Dockerfile` (verify — no file changes)

**Requirements**: AC1

**Dependencies**: T1.1, T1.2

**Command**:

```bash
docker build -t tsisip/admin-api:test -f docker/admin-api/Dockerfile .
```

**Verification**: Build completes with exit code 0 and produces image `tsisip/admin-api:test`.

---

### T1.4: Run Trivy scan on pinned digest to verify no new HIGH/CRITICAL CVEs

**File**: None (runtime verification)

**Requirements**: R2, SR-1

**Dependencies**: T1.3

**Command**:

```bash
trivy image --severity HIGH,CRITICAL --exit-code 1 \
  php:8.2-apache@sha256:4e65fefba73c49d80ff4b6f2c03e691b7437e9f1c315f5b4f5f2f3e6a7b8c9d0
```

**Verification**: Trivy exits with code 0 (no new HIGH/CRITICAL CVEs introduced by the pinned digest).

---

### T1.5: Capture Trivy scan evidence in docs/security/evidence/024-trivy-scan.txt

**File**: `docs/security/evidence/024-trivy-scan.txt` (new)

**Requirements**: R2

**Dependencies**: T1.4

**Content**:

```text
# Trivy Scan Evidence — Feature 024
# Date: 2026-05-24
# Image: php:8.2-apache@sha256:4e65fefba73c49d80ff4b6f2c03e691b7437e9f1c315f5b4f5f2f3e6a7b8c9d0
# Command: trivy image --severity HIGH,CRITICAL php:8.2-apache@sha256:...

## Result

[Paste full Trivy output here]

## Gate Status

SR-1: PASS / FAIL
- HIGH CVEs: <count>
- CRITICAL CVEs: <count>
```

**Verification**: File exists and contains Trivy output with explicit SR-1 PASS/FAIL determination.

---

## Phase 2: Test Script Hygiene (B2–B3)

### T2.1: Create get_test_ip() helper in tests/integration/test_end_to_end_call.py

**File**: `tests/integration/test_end_to_end_call.py` (modify)

**Requirements**: AC2

**Dependencies**: None

**Before** (lines 1–10):

```python
"""
Feature 007 Integration Tests: End-to-End SIP Call Flow
Validates: REGISTER -> 401 -> REGISTER(auth) -> 200 -> INVITE -> ROUTE -> Asterisk
"""
import os
import pytest
import socket
import hashlib
import time
```

**After** (lines 1–34):

```python
"""
Feature 007 Integration Tests: End-to-End SIP Call Flow
Validates: REGISTER -> 401 -> REGISTER(auth) -> 200 -> INVITE -> ROUTE -> Asterisk
"""
import os
import pytest
import socket
import hashlib
import subprocess
import time


def get_test_ip() -> str:
    """Return the IP address to use for SIP test messages.

    Priority:
    1. TEST_IP environment variable
    2. Docker network inspect for tsisip_sip_edge gateway
    3. Fallback to 127.0.0.1
    """
    env_ip = os.environ.get("TEST_IP")
    if env_ip:
        return env_ip

    try:
        result = subprocess.run(
            [
                "docker", "network", "inspect", "tsisip_sip_edge",
                "--format", "{{range .IPAM.Config}}{{.Gateway}}{{end}}",
            ],
            capture_output=True,
            text=True,
            timeout=5,
        )
        if result.returncode == 0 and result.stdout.strip():
            return result.stdout.strip().split("\n")[0]
    except Exception:
        pass

    return "127.0.0.1"
```

**Verification**: `python3 -c "from tests.integration.test_end_to_end_call import get_test_ip; print(get_test_ip())"` returns a valid IP string.

---

### T2.2: Replace all hard-coded 172.x IPs in test_end_to_end_call.py with TEST_IP env var

**File**: `tests/integration/test_end_to_end_call.py` (modify)

**Requirements**: AC2

**Dependencies**: T2.1

**Before** (lines 16–49):

```python
def _build_register(
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    username: str = "devuser",
    domain: str = "dev.tsisip.local",
    with_auth: bool = False,
    nonce: str = None,
    uri: str = None,
) -> bytes:
    if uri is None:
        uri = f"sip:{domain}"
    msg = (
        f"REGISTER {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP 172.22.0.1:5061;branch={branch}\r\n"
        f"From: <sip:{username}@{domain}>;tag={from_tag}\r\n"
        f"To: <sip:{username}@{domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} REGISTER\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{username}@172.22.0.1:5061>\r\n"
    )
```

**After** (lines 37–71):

```python
def _build_register(
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    username: str = "devuser",
    domain: str = "dev.tsisip.local",
    with_auth: bool = False,
    nonce: str = None,
    uri: str = None,
) -> bytes:
    test_ip = get_test_ip()
    if uri is None:
        uri = f"sip:{domain}"
    msg = (
        f"REGISTER {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {test_ip}:5061;branch={branch}\r\n"
        f"From: <sip:{username}@{domain}>;tag={from_tag}\r\n"
        f"To: <sip:{username}@{domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} REGISTER\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{username}@{test_ip}:5061>\r\n"
    )
```

**Before** (lines 52–93):

```python
def _build_invite(
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    to_user: str = "1000",
    username: str = "devuser",
    domain: str = "dev.tsisip.local",
    nonce: str = None,
) -> bytes:
    uri = f"sip:{to_user}@{domain}"
    msg = (
        f"INVITE {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP 172.22.0.1:5061;branch={branch}\r\n"
        f"From: <sip:{username}@{domain}>;tag={from_tag}\r\n"
        f"To: <sip:{to_user}@{domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{username}@172.22.0.1:5061>\r\n"
    )
```

**After** (lines 74–117):

```python
def _build_invite(
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    to_user: str = "1000",
    username: str = "devuser",
    domain: str = "dev.tsisip.local",
    nonce: str = None,
) -> bytes:
    test_ip = get_test_ip()
    uri = f"sip:{to_user}@{domain}"
    msg = (
        f"INVITE {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {test_ip}:5061;branch={branch}\r\n"
        f"From: <sip:{username}@{domain}>;tag={from_tag}\r\n"
        f"To: <sip:{to_user}@{domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{username}@{test_ip}:5061>\r\n"
    )
    if nonce:
        ha1 = _ha1_md5(username, domain, "devpass")
        ha2 = hashlib.md5(f"INVITE:{uri}".encode()).hexdigest()
        response = hashlib.md5(f"{ha1}:{nonce}:{ha2}".encode()).hexdigest()
        msg += (
            f'Authorization: Digest username="{username}", '
            f'realm="{domain}", nonce="{nonce}", uri="{uri}", '
            f'response="{response}", algorithm=MD5\r\n'
        )
    sdp = (
        "v=0\r\n"
        f"o=- 0 0 IN IP4 {test_ip}\r\n"
        "s=TSiSIP Test\r\n"
        f"c=IN IP4 {test_ip}\r\n"
        "t=0 0\r\n"
        "m=audio 10000 RTP/AVP 0 8\r\n"
        "a=rtpmap:0 PCMU/8000\r\n"
        "a=rtpmap:8 PCMA/8000\r\n"
    )
    msg += f"Content-Type: application/sdp\r\nContent-Length: {len(sdp)}\r\n\r\n{sdp}"
    return msg.encode()
```

Also update `test_register_unauthorized`, `test_register_authenticated`, and `test_invite_routes_to_asterisk` to use dynamic Call-ID domains:

**Before** (lines 123–130):

```python
    def test_register_unauthorized(self):
        """Unauthenticated REGISTER receives 401 with nonce."""
        msg = _build_register(
            call_id="test-register-001@172.22.0.1",
            cseq=1,
            from_tag="regtag001",
            branch="z9hG4bK-reg001",
        )
```

**After** (lines 145–153):

```python
    def test_register_unauthorized(self):
        """Unauthenticated REGISTER receives 401 with nonce."""
        test_ip = get_test_ip()
        msg = _build_register(
            call_id=f"test-register-001@{test_ip}",
            cseq=1,
            from_tag="regtag001",
            branch="z9hG4bK-reg001",
        )
```

Repeat the same pattern for `test_register_authenticated` (call_id `test-register-002@{test_ip}`) and `test_invite_routes_to_asterisk`.

**Verification**: `grep -n "172\.22\.0\.1" tests/integration/test_end_to_end_call.py` returns zero matches.

---

### T2.3: Create get_test_ip() helper in tests/integration/test_sip_trunk_failover.py

**File**: `tests/integration/test_sip_trunk_failover.py` (modify)

**Requirements**: AC3

**Dependencies**: None

**Before** (lines 19–25):

```python
import hashlib
import os
import socket
import subprocess
import threading
import time
import unittest
```

**After** (lines 19–48):

```python
import hashlib
import os
import socket
import subprocess
import threading
import time
import unittest


def get_test_ip() -> str:
    """Return the IP address to use for SIP test messages.

    Priority:
    1. TEST_IP environment variable
    2. Docker network inspect for tsisip_sip_edge gateway
    3. Fallback to 127.0.0.1
    """
    env_ip = os.environ.get("TEST_IP")
    if env_ip:
        return env_ip

    try:
        result = subprocess.run(
            [
                "docker", "network", "inspect", "tsisip_sip_edge",
                "--format", "{{range .IPAM.Config}}{{.Gateway}}{{end}}",
            ],
            capture_output=True,
            text=True,
            timeout=5,
        )
        if result.returncode == 0 and result.stdout.strip():
            return result.stdout.strip().split("\n")[0]
    except Exception:
        pass

    return "127.0.0.1"
```

**Verification**: `python3 -c "from tests.integration.test_sip_trunk_failover import get_test_ip; print(get_test_ip())"` returns a valid IP string.

---

### T2.4: Replace all hard-coded 172.x IPs in test_sip_trunk_failover.py with TEST_IP env var

**File**: `tests/integration/test_sip_trunk_failover.py` (modify)

**Requirements**: AC3

**Dependencies**: T2.3

**Before** (lines 77–106):

```python
def _build_register(
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    with_auth: bool = False,
    nonce: str = None,
) -> bytes:
    uri = f"sip:{TEST_DOMAIN}"
    msg = (
        f"REGISTER {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP 172.22.0.1:5061;branch={branch}\r\n"
        f"From: <sip:{TEST_CALLER}@{TEST_DOMAIN}>;tag={from_tag}\r\n"
        f"To: <sip:{TEST_CALLER}@{TEST_DOMAIN}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} REGISTER\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{TEST_CALLER}@172.22.0.1:5061>\r\n"
    )
```

**After** (lines 107–137):

```python
def _build_register(
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    with_auth: bool = False,
    nonce: str = None,
) -> bytes:
    test_ip = get_test_ip()
    uri = f"sip:{TEST_DOMAIN}"
    msg = (
        f"REGISTER {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {test_ip}:5061;branch={branch}\r\n"
        f"From: <sip:{TEST_CALLER}@{TEST_DOMAIN}>;tag={from_tag}\r\n"
        f"To: <sip:{TEST_CALLER}@{TEST_DOMAIN}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} REGISTER\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{TEST_CALLER}@{test_ip}:5061>\r\n"
    )
```

**Before** (lines 109–150):

```python
def _build_invite(
    to_user: str,
    to_domain: str,
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    with_auth: bool = False,
    nonce: str = None,
) -> bytes:
    uri = f"sip:{to_user}@{to_domain}"
    msg = (
        f"INVITE {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP 172.22.0.1:5061;branch={branch}\r\n"
        f"From: <sip:{TEST_CALLER}@{TEST_DOMAIN}>;tag={from_tag}\r\n"
        f"To: <sip:{to_user}@{to_domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{TEST_CALLER}@172.22.0.1:5061>\r\n"
    )
```

**After** (lines 140–182):

```python
def _build_invite(
    to_user: str,
    to_domain: str,
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    with_auth: bool = False,
    nonce: str = None,
) -> bytes:
    test_ip = get_test_ip()
    uri = f"sip:{to_user}@{to_domain}"
    msg = (
        f"INVITE {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {test_ip}:5061;branch={branch}\r\n"
        f"From: <sip:{TEST_CALLER}@{TEST_DOMAIN}>;tag={from_tag}\r\n"
        f"To: <sip:{to_user}@{to_domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{TEST_CALLER}@{test_ip}:5061>\r\n"
    )
    if with_auth and nonce:
        ha1 = _ha1_md5(TEST_CALLER, TEST_DOMAIN, TEST_PASSWORD)
        ha2 = hashlib.md5(f"INVITE:{uri}".encode()).hexdigest()
        response = hashlib.md5(f"{ha1}:{nonce}:{ha2}".encode()).hexdigest()
        msg += (
            f'Proxy-Authorization: Digest username="{TEST_CALLER}", '
            f'realm="{TEST_DOMAIN}", nonce="{nonce}", uri="{uri}", '
            f'response="{response}", algorithm=MD5\r\n'
        )
    sdp = (
        "v=0\r\n"
        f"o=- 0 0 IN IP4 {test_ip}\r\n"
        "s=TSiSIP Test\r\n"
        f"c=IN IP4 {test_ip}\r\n"
        "t=0 0\r\n"
        "m=audio 10000 RTP/AVP 0 8\r\n"
        "a=rtpmap:0 PCMU/8000\r\n"
        "a=rtpmap:8 PCMA/8000\r\n"
    )
    msg += f"Content-Type: application/sdp\r\nContent-Length: {len(sdp)}\r\n\r\n{sdp}"
    return msg.encode()
```

Also update all `call_id` parameters in test methods to use `get_test_ip()`:

**Before** (lines 266–275):

```python
        ping = (
            b"OPTIONS sip:" + TARGET_HOST.encode() + b":" + str(TARGET_PORT).encode() + b" SIP/2.0\r\n"
            b"Via: SIP/2.0/UDP 172.22.0.1:5061;branch=z9hG4bK-ping\r\n"
            b"From: <sip:test@localhost>;tag=ping\r\n"
            b"To: <sip:" + TARGET_HOST.encode() + b":" + str(TARGET_PORT).encode() + b">\r\n"
            b"Call-ID: ping-001@172.22.0.1\r\n"
            b"CSeq: 1 OPTIONS\r\n"
            b"Max-Forwards: 70\r\n"
            b"Content-Length: 0\r\n\r\n"
        )
```

**After** (lines 298–308):

```python
        test_ip = get_test_ip()
        ping = (
            b"OPTIONS sip:" + TARGET_HOST.encode() + b":" + str(TARGET_PORT).encode() + b" SIP/2.0\r\n"
            f"Via: SIP/2.0/UDP {test_ip}:5061;branch=z9hG4bK-ping\r\n".encode()
            b"From: <sip:test@localhost>;tag=ping\r\n"
            b"To: <sip:" + TARGET_HOST.encode() + b":" + str(TARGET_PORT).encode() + b">\r\n"
            f"Call-ID: ping-001@{test_ip}\r\n".encode()
            b"CSeq: 1 OPTIONS\r\n"
            b"Max-Forwards: 70\r\n"
            b"Content-Length: 0\r\n\r\n"
        )
```

And update `_authenticate` method call_ids:
- `trunk-fail-reg-001@172.22.0.1` → `f"trunk-fail-reg-001@{get_test_ip()}"`
- `trunk-fail-inv-001@172.22.0.1` → `f"trunk-fail-inv-001@{get_test_ip()}"`
- `trunk-fail-inv-002@172.22.0.1` → `f"trunk-fail-inv-002@{get_test_ip()}"`

**Verification**: `grep -n "172\.22\.0\.1" tests/integration/test_sip_trunk_failover.py` returns zero matches.

---

### T2.5: Run pytest smoke test with TEST_IP=127.0.0.1 to verify parameterization

**File**: None (runtime verification)

**Requirements**: AC2, AC3

**Dependencies**: T2.2, T2.4

**Command**:

```bash
TEST_IP=127.0.0.1 python3 -m pytest tests/integration/test_end_to_end_call.py tests/integration/test_sip_trunk_failover.py -v --tb=short -x
```

**Verification**: pytest runs without `NameError` or `AttributeError` related to `get_test_ip`. Tests may skip due to missing Docker stack, but must not fail because of IP parameterization.

---

## Phase 3: Deploy Script Robustness (B4–B6, B8–B10)

### T3.1: Replace static RTPENGINE_PRIVATE_IP in deploy/scripts/test-vps-local.sh with docker network inspect

**File**: `deploy/scripts/test-vps-local.sh` (modify)

**Requirements**: AC4

**Dependencies**: None

**Before** (lines 38–44):

```bash
# .env de teste
cat > "${PROJECT_ROOT}/.env" <<'ENVEOF'
OPENSIPS_LISTEN_IP=0.0.0.0
HOST_PUBLIC_IP=127.0.0.1
RTPENGINE_PRIVATE_IP=172.19.0.1
RTPENGINE_INTERNAL_IP=172.21.0.1
ENVEOF
```

**After** (lines 38–52):

```bash
# Discover Docker network IPs dynamically
discover_network_ip() {
    local network="$1"
    local ip
    ip=$(docker network inspect "${network}" --format='{{range .IPAM.Config}}{{.Gateway}}{{end}}' 2>/dev/null || true)
    if [[ -z "${ip}" ]]; then
        echo "[ERROR] Failed to discover IP for Docker network: ${network}" >&2
        exit 1
    fi
    echo "${ip}"
}

RTPENGINE_PRIVATE_IP=$(discover_network_ip "tsisip_sip_edge")
RTPENGINE_INTERNAL_IP=$(discover_network_ip "tsisip_sip_internal")

# .env de teste
cat > "${PROJECT_ROOT}/.env" <<ENVEOF
OPENSIPS_LISTEN_IP=0.0.0.0
HOST_PUBLIC_IP=127.0.0.1
RTPENGINE_PRIVATE_IP=${RTPENGINE_PRIVATE_IP}
RTPENGINE_INTERNAL_IP=${RTPENGINE_INTERNAL_IP}
ENVEOF
```

**Verification**: `bash -n deploy/scripts/test-vps-local.sh` passes syntax check; script runs without hard-coded 172.x IPs.

---

### T3.2: Replace static RTPENGINE_INTERNAL_IP in deploy/scripts/test-vps-local.sh with docker network inspect

**File**: `deploy/scripts/test-vps-local.sh` (modify — combined with T3.1)

**Requirements**: AC4

**Dependencies**: T3.1

**Note**: Implemented in the same change block as T3.1 above. Both variables are discovered by the `discover_network_ip` helper.

**Verification**: `grep "172\.19\.0\.1\|172\.21\.0\.1" deploy/scripts/test-vps-local.sh` returns zero matches.

---

### T3.3: Replace static RTPENGINE_PRIVATE_IP in deploy/scripts/vps-bootstrap.sh with docker network inspect

**File**: `deploy/scripts/vps-bootstrap.sh` (modify)

**Requirements**: AC4

**Dependencies**: None

**Before** (lines 108–115):

```bash
# === 6. .env ===
log_info "Configurando .env..."
cat > "${TSISIP_DIR}/.env" <<ENVEOF
OPENSIPS_LISTEN_IP=0.0.0.0
HOST_PUBLIC_IP=179.190.15.116
RTPENGINE_PRIVATE_IP=172.19.0.1
RTPENGINE_INTERNAL_IP=172.21.0.1
ENVEOF
```

**After** (lines 108–125):

```bash
# === 6. .env ===
log_info "Configurando .env..."

# Discover Docker network IPs dynamically (survives network recreation)
discover_network_ip() {
    local network="$1"
    local ip
    ip=$(docker network inspect "${network}" --format='{{range .IPAM.Config}}{{.Gateway}}{{end}}' 2>/dev/null || true)
    if [[ -z "${ip}" ]]; then
        echo "[ERROR] Failed to discover IP for Docker network: ${network}" >&2
        exit 1
    fi
    echo "${ip}"
}

RTPENGINE_PRIVATE_IP=$(discover_network_ip "tsisip_sip_edge")
RTPENGINE_INTERNAL_IP=$(discover_network_ip "tsisip_sip_internal")

cat > "${TSISIP_DIR}/.env" <<ENVEOF
OPENSIPS_LISTEN_IP=0.0.0.0
HOST_PUBLIC_IP=179.190.15.116
RTPENGINE_PRIVATE_IP=${RTPENGINE_PRIVATE_IP}
RTPENGINE_INTERNAL_IP=${RTPENGINE_INTERNAL_IP}
ENVEOF
```

**Verification**: `bash -n deploy/scripts/vps-bootstrap.sh` passes syntax check.

---

### T3.4: Replace static RTPENGINE_INTERNAL_IP in deploy/scripts/vps-bootstrap.sh with docker network inspect

**File**: `deploy/scripts/vps-bootstrap.sh` (modify — combined with T3.3)

**Requirements**: AC4

**Dependencies**: T3.3

**Note**: Implemented in the same change block as T3.3 above.

**Verification**: `grep "172\.19\.0\.1\|172\.21\.0\.1" deploy/scripts/vps-bootstrap.sh` returns zero matches.

---

### T3.5: Replace static RTPENGINE_PRIVATE_IP in deploy/scripts/vps-deploy.sh with docker network inspect

**File**: `deploy/scripts/vps-deploy.sh` (modify)

**Requirements**: AC4

**Dependencies**: None

**Before** (lines 90–109):

```bash
if [[ ! -f "${ENV_FILE}" ]]; then
    log_warn ".env nao encontrado. Criando com defaults..."
    cat > "${ENV_FILE}" <<'EOF'
OPENSIPS_LISTEN_IP=0.0.0.0
HOST_PUBLIC_IP=127.0.0.1
RTPENGINE_PRIVATE_IP=172.19.0.1
RTPENGINE_INTERNAL_IP=172.21.0.1
EOF
fi

if grep -q 'RTPENGINE_INTERNAL_IP=10\.0\.0\.2' "${ENV_FILE}" 2>/dev/null; then
    # Discover the actual Docker network gateway for sip_internal
    RTPENGINE_INTERNAL_IP=$(docker network inspect tsisip_sip_internal --format='{{range .IPAM.Config}}{{.Gateway}}{{end}}' 2>/dev/null || echo "172.21.0.1")
    if grep -q "^RTPENGINE_INTERNAL_IP=" "${ENV_FILE}" 2>/dev/null; then
        sed -i "s|^RTPENGINE_INTERNAL_IP=.*|RTPENGINE_INTERNAL_IP=${RTPENGINE_INTERNAL_IP}|" "${ENV_FILE}"
    else
        echo "RTPENGINE_INTERNAL_IP=${RTPENGINE_INTERNAL_IP}" >> "${ENV_FILE}"
    fi
    log_warn "Ajustado RTPENGINE_INTERNAL_IP para ${RTPENGINE_INTERNAL_IP} (descoberto da rede Docker)"
fi
```

**After** (lines 90–120):

```bash
# Discover Docker network IPs dynamically (survives network recreation)
discover_network_ip() {
    local network="$1"
    local ip
    ip=$(docker network inspect "${network}" --format='{{range .IPAM.Config}}{{.Gateway}}{{end}}' 2>/dev/null || true)
    if [[ -z "${ip}" ]]; then
        log_fatal "Failed to discover IP for Docker network: ${network}"
    fi
    echo "${ip}"
}

if [[ ! -f "${ENV_FILE}" ]]; then
    log_warn ".env nao encontrado. Criando com defaults..."
    RTPENGINE_PRIVATE_IP=$(discover_network_ip "tsisip_sip_edge")
    RTPENGINE_INTERNAL_IP=$(discover_network_ip "tsisip_sip_internal")
    cat > "${ENV_FILE}" <<EOF
OPENSIPS_LISTEN_IP=0.0.0.0
HOST_PUBLIC_IP=127.0.0.1
RTPENGINE_PRIVATE_IP=${RTPENGINE_PRIVATE_IP}
RTPENGINE_INTERNAL_IP=${RTPENGINE_INTERNAL_IP}
EOF
fi

# Ensure both RTPENGINE IPs are discovered dynamically, overwriting any stale defaults
RTPENGINE_PRIVATE_IP=$(discover_network_ip "tsisip_sip_edge")
RTPENGINE_INTERNAL_IP=$(discover_network_ip "tsisip_sip_internal")

for var in RTPENGINE_PRIVATE_IP RTPENGINE_INTERNAL_IP; do
    val="${!var}"
    if grep -q "^${var}=" "${ENV_FILE}" 2>/dev/null; then
        sed -i "s|^${var}=.*|${var}=${val}|" "${ENV_FILE}"
    else
        echo "${var}=${val}" >> "${ENV_FILE}"
    fi
done
log_info "RTPENGINE IPs atualizados: PRIVATE=${RTPENGINE_PRIVATE_IP} INTERNAL=${RTPENGINE_INTERNAL_IP}"
```

**Verification**: `bash -n deploy/scripts/vps-deploy.sh` passes syntax check; `grep "172\.19\.0\.1\|172\.21\.0\.1" deploy/scripts/vps-deploy.sh` returns zero matches.

---

### T3.6: Replace static RTPENGINE_INTERNAL_IP in deploy/scripts/vps-deploy.sh with docker network inspect

**File**: `deploy/scripts/vps-deploy.sh` (modify — combined with T3.5)

**Requirements**: AC4

**Dependencies**: T3.5

**Note**: Implemented in the same change block as T3.5 above. The old partial dynamic discovery (line 102) is replaced by the unified `discover_network_ip` helper.

**Verification**: No standalone `docker network inspect` fallback with `|| echo` remains in the file.

---

### T3.7: Add error handling: exit 1 with descriptive message if docker network inspect fails

**File**: `deploy/scripts/test-vps-local.sh`, `deploy/scripts/vps-bootstrap.sh`, `deploy/scripts/vps-deploy.sh` (modify)

**Requirements**: AC4, R3, SR-2

**Dependencies**: T3.1–T3.6

**Implementation**: The `discover_network_ip` helper added in T3.1, T3.3, and T3.5 already contains the strict error handling:

```bash
discover_network_ip() {
    local network="$1"
    local ip
    ip=$(docker network inspect "${network}" --format='{{range .IPAM.Config}}{{.Gateway}}{{end}}' 2>/dev/null || true)
    if [[ -z "${ip}" ]]; then
        # test-vps-local.sh and vps-bootstrap.sh use echo + exit 1
        # vps-deploy.sh uses log_fatal (which exits 1)
        echo "[ERROR] Failed to discover IP for Docker network: ${network}" >&2
        exit 1
    fi
    echo "${ip}"
}
```

**Additional requirement for SR-2**: Ensure discovered IPs are never logged to stdout in CI contexts. The helpers above echo errors to stderr (`>&2`) and only log the IP internally to the `.env` file. No `echo "${ip}"` outside of variable assignment.

**Verification**:
- `bash deploy/scripts/test-vps-local.sh` with a missing Docker network fails with exit code 1 and stderr message.
- `grep -n "echo.*172\|echo.*RTPENGINE" deploy/scripts/test-vps-local.sh deploy/scripts/vps-bootstrap.sh deploy/scripts/vps-deploy.sh` shows no IP leakage to stdout.

---

### T3.8: Add inline comments before every sleep in deploy/scripts/orchestrate-deploy.sh explaining wait purpose

**File**: `deploy/scripts/orchestrate-deploy.sh` (modify)

**Requirements**: AC6

**Dependencies**: None

**Before** (line 474):

```bash
    sleep 10
```

**After** (lines 473–475):

```bash
    # Allow containers time to start and stabilize before health checks
    sleep 10
```

**Verification**: `grep -B1 "sleep" deploy/scripts/orchestrate-deploy.sh | grep -v "^--$" | grep -c "#"` shows every sleep has a preceding comment.

---

### T3.9: Add inline comments before every sleep in deploy/scripts/safe-recovery.sh explaining wait purpose

**File**: `deploy/scripts/safe-recovery.sh` (modify)

**Requirements**: AC6

**Dependencies**: None

**Before** (lines 45, 73):

```bash
    sleep 10
```

```bash
sleep 5
```

**After** (lines 44–46):

```bash
    # Wait for containers in this phase to reach healthy state before starting next phase
    sleep 10
```

**After** (lines 72–74):

```bash
# Brief pause for health endpoints to become ready before probing
sleep 5
```

**Verification**: `grep -B1 "sleep" deploy/scripts/safe-recovery.sh | grep -v "^--$" | grep -c "#"` shows every sleep has a preceding comment.

---

### T3.10: Add inline comments before every sleep in deploy/scripts/vps-deploy.sh explaining wait purpose

**File**: `deploy/scripts/vps-deploy.sh` (modify)

**Requirements**: AC6

**Dependencies**: None

**Before** (lines 131, 165):

```bash
    sleep 2
```

**After** (lines 130–132):

```bash
    # Poll PostgreSQL health every 2 seconds until healthy or timeout
    sleep 2
```

**After** (lines 164–166):

```bash
    # Poll OpenSIPS health every 2 seconds until healthy or timeout
    sleep 2
```

**Verification**: `grep -B1 "sleep" deploy/scripts/vps-deploy.sh | grep -v "^--$" | grep -c "#"` shows every sleep has a preceding comment.

---

### T3.11: Verify with grep that all sleep statements in deploy/scripts/*.sh have preceding comments

**File**: None (runtime verification)

**Requirements**: AC6

**Dependencies**: T3.8–T3.10

**Command**:

```bash
#!/bin/bash
set -euo pipefail

missing=0
for f in deploy/scripts/*.sh; do
    # Find sleep lines that are not preceded by a comment line
    awk '
        /^[[:space:]]*sleep / {
            if (prev !~ /^[[:space:]]*#/) {
                print FILENAME ":" NR ": " $0
                exit 1
            }
        }
        { prev = $0 }
    ' "$f" || missing=$((missing + 1))
done

if [[ "$missing" -gt 0 ]]; then
    echo "FAIL: $missing files have uncommented sleep statements"
    exit 1
fi

echo "PASS: All sleep statements have preceding comments"
```

**Verification**: Script exits 0 with "PASS" message.

---

## Phase 4: Configuration Completeness (B7)

### T4.1: Extract all variable references from docker-compose.vps.yml using grep

**File**: None (runtime analysis)

**Requirements**: AC5

**Dependencies**: None

**Command**:

```bash
grep -oP '\$\{\K[A-Za-z_][A-Za-z0-9_]*' docker-compose.vps.yml | sort -u
```

**Expected output**:

```text
ACME_EMAIL
ACME_SERVER
CERTBOT_STAGING
HOST_PUBLIC_IP
OCP_AUDIT_RETENTION_DAYS
OPENSIPS_LISTEN_IP
RTPENGINE_INTERNAL_IP
RTPENGINE_PRIVATE_IP
SUBSCRIBER_CREATE_RATE_LIMIT
SUBSCRIBER_DELETE_RATE_LIMIT
SUBSCRIBER_UPDATE_RATE_LIMIT
TLS_DOMAIN
TSISIP_IMAGE_TAG
```

**Verification**: Output contains 13 unique variable names.

---

### T4.2: Audit existing .env.example and identify missing variables

**File**: `.env.example` (audit — no file changes yet)

**Requirements**: AC5

**Dependencies**: T4.1

**Missing variables identified**:

| Variable | Default | Used By |
|----------|---------|---------|
| SUBSCRIBER_CREATE_RATE_LIMIT | 10 | admin-api service |
| SUBSCRIBER_UPDATE_RATE_LIMIT | 30 | admin-api service |
| SUBSCRIBER_DELETE_RATE_LIMIT | 10 | admin-api service |

All other 10 variables from T4.1 are already present in `.env.example`.

**Verification**: Cross-reference of T4.1 output against `.env.example` confirms exactly 3 missing variables.

---

### T4.3: Add all missing variables to .env.example with placeholder values and descriptive comments

**File**: `.env.example` (modify)

**Requirements**: AC5, AD-024-2

**Dependencies**: T4.2

**Before** (line 46):

```bash
# OCP audit log retention (Feature 014-B Wave 5)
OCP_AUDIT_RETENTION_DAYS=90
```

**After** (lines 46–55):

```bash
# OCP audit log retention (Feature 014-B Wave 5)
OCP_AUDIT_RETENTION_DAYS=90

# Admin API rate limits (requests per minute)
# These limits protect the subscriber management endpoints from abuse.
SUBSCRIBER_CREATE_RATE_LIMIT=10
SUBSCRIBER_UPDATE_RATE_LIMIT=30
SUBSCRIBER_DELETE_RATE_LIMIT=10
```

**Verification**: `grep "SUBSCRIBER_.*_RATE_LIMIT" .env.example` returns 3 matches with descriptive comments.

---

### T4.4: Validate docker compose config with placeholder env values

**File**: None (runtime verification)

**Requirements**: AC5, AC9

**Dependencies**: T4.3

**Command**:

```bash
# Ensure secrets exist for validation
mkdir -p secrets
touch secrets/db_password secrets/auth_secret secrets/topology_secret \
      secrets/backup_encryption_key secrets/ca.crt secrets/server.crt \
      secrets/server.key secrets/crl.pem secrets/rclone_s3_access_key \
      secrets/rclone_s3_secret_key secrets/trunk_cred_key secrets/proxy_api_secret

docker compose -f docker-compose.vps.yml config > /dev/null
```

**Verification**: `docker compose config` exits with code 0 and no errors.

---

## Phase 5: Healthcheck Hardening (B11–B12)

### Pre-completed Tasks

| Task | File | Status |
|------|------|--------|
| T5.5: Add HEALTHCHECK to docker/anomaly-detector/Dockerfile | `docker/anomaly-detector/Dockerfile` | Already complete — file already contains `HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 CMD curl -f http://localhost:8080/health` which matches AD-024-3 requirements. |

> **Note**: T5.5 is pre-completed and does NOT require a full heading or code block below. The existing HEALTHCHECK satisfies the requirement.

---

### T5.1: Run docker compose exec ocp curl to verify current healthcheck behavior inside container namespace

**File**: None (runtime verification)

**Requirements**: AC7

**Dependencies**: None

**Command** (requires running stack):

```bash
docker compose -f docker-compose.vps.yml exec ocp \
    sh -c "curl -fsS http://127.0.0.1/login.php > /dev/null && echo PASS || echo FAIL"
```

**Expected result**: `PASS` — The OCP container's internal loopback (`127.0.0.1`) resolves to the container itself regardless of the host's `userland-proxy=false` setting. The `userland-proxy` setting only affects host-to-container port forwarding, not container-internal networking.

**Verification**: Command returns `PASS` with exit code 0.

---

### T5.2: If healthcheck fails with userland-proxy=false, update docker-compose.vps.yml healthcheck

**File**: `docker-compose.vps.yml` (conditional modify)

**Requirements**: AC7

**Dependencies**: T5.1

**Expected outcome**: T5.1 passes without modification. No changes to `docker-compose.vps.yml` are required because the current healthcheck uses `127.0.0.1` inside the container namespace, which is unaffected by `userland-proxy=false`.

> **Note**: The host-side accessibility of `127.0.0.1:8084` (published port) is a separate concern from the container healthcheck. Host access with `userland-proxy=false` requires connecting via the container's Docker bridge IP or using an nginx proxy, as documented in the compose file comments (lines 242–244).

**If T5.1 unexpectedly fails**, update the healthcheck to use the container's service name:

**Conditional After** (line 263):

```yaml
    healthcheck:
      test: ["CMD-SHELL", "curl -fsS http://ocp/login.php > /dev/null || exit 1"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 10s
```

**Verification**: T5.1 passes (no modification needed) or updated healthcheck passes `docker compose exec ocp curl`.

---

### T5.3: Add HEALTHCHECK to docker/admin-api/Dockerfile with 30s interval, 60s start_period, 3 retries

**File**: `docker/admin-api/Dockerfile` (modify)

**Requirements**: AC8, AD-024-3

**Dependencies**: None

**Before** (lines 25–27):

```dockerfile
# Security: run as non-root (www-data is default in php-apache)
# Drop all capabilities except necessary ones at runtime via compose
```

**After** (lines 25–29):

```dockerfile
# Health check — lightweight HTTP probe on internal port
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/ > /dev/null || exit 1

# Security: run as non-root (www-data is default in php-apache)
# Drop all capabilities except necessary ones at runtime via compose
```

**Verification**: `docker build -t tsisip/admin-api:healthcheck-test -f docker/admin-api/Dockerfile .` succeeds.

---

### T5.4: Add HEALTHCHECK to docker/backup/Dockerfile with file-based or command-based probe

**File**: `docker/backup/Dockerfile` (modify)

**Requirements**: AC8, AD-024-3

**Dependencies**: None

**Before** (lines 50–52):

```dockerfile
# Health check
HEALTHCHECK --interval=60s --timeout=10s --start-period=30s --retries=3 \
    CMD pg_isready -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" || exit 1
```

**After** (lines 50–52):

```dockerfile
# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD pg_isready -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" || exit 1
```

> **Note**: Only the `interval` changes from `60s` to `30s` per AD-024-3. The `pg_isready` probe remains the correct health indicator for a PostgreSQL backup container.

**Verification**: `docker build -t tsisip/backup:healthcheck-test -f docker/backup/Dockerfile docker/backup/` succeeds.

---

### T5.6: Add HEALTHCHECK to docker/ca-tool/Dockerfile with certificate validity check

**File**: `docker/ca-tool/Dockerfile` (modify)

**Requirements**: AC8, AD-024-3

**Dependencies**: None

**Before** (lines 25–27):

```dockerfile
VOLUME ["/ca/output", "/ca/secrets"]

CMD ["bash"]
```

**After** (lines 25–30):

```dockerfile
VOLUME ["/ca/output", "/ca/secrets"]

# Health check — verify CA directory structure and openssl availability
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD test -d /ca/root/private && test -d /ca/output && openssl version > /dev/null || exit 1

CMD ["bash"]
```

**Verification**: `docker build -t tsisip/ca-tool:healthcheck-test -f docker/ca-tool/Dockerfile docker/ca-tool/` succeeds.

---

### T5.7: Add HEALTHCHECK to docker/certbot-exporter/Dockerfile with metrics endpoint probe

**File**: `docker/certbot-exporter/Dockerfile` (modify)

**Requirements**: AC8, AD-024-3

**Dependencies**: None

**Before** (lines 20–22):

```dockerfile
EXPOSE 9101

ENTRYPOINT ["python3", "/app/exporter.py"]
```

**After** (lines 20–25):

```dockerfile
EXPOSE 9101

# Health check — verify metrics endpoint responds with certbot data
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS http://localhost:9101/metrics | grep -q "certbot" || exit 1

ENTRYPOINT ["python3", "/app/exporter.py"]
```

**Verification**: `docker build -t tsisip/certbot-exporter:healthcheck-test -f docker/certbot-exporter/Dockerfile docker/certbot-exporter/` succeeds.

---

### T5.8: Build each modified image and verify health status transitions to healthy within 60s

**File**: None (runtime verification)

**Requirements**: AC8

**Dependencies**: T5.3–T5.7

**Command**:

```bash
#!/bin/bash
set -euo pipefail

images=(
    "docker/admin-api/Dockerfile:tsisip/admin-api:healthcheck-test"
    "docker/backup/Dockerfile:tsisip/backup:healthcheck-test"
    "docker/ca-tool/Dockerfile:tsisip/ca-tool:healthcheck-test"
    "docker/certbot-exporter/Dockerfile:tsisip/certbot-exporter:healthcheck-test"
)

for spec in "${images[@]}"; do
    dockerfile="${spec%%:*}"
    tag="${spec##*:}"
    context=$(dirname "$dockerfile")
    
    echo "Building ${tag}..."
    docker build -t "${tag}" -f "${dockerfile}" "${context}"
    
    echo "Checking health for ${tag}..."
    docker run -d --name healthcheck-test --health-interval=2s --health-timeout=2s --health-retries=3 "${tag}"
    
    for i in {1..30}; do
        status=$(docker inspect healthcheck-test --format='{{.State.Health.Status}}' 2>/dev/null || echo "starting")
        if [[ "${status}" == "healthy" ]]; then
            echo "PASS: ${tag} is healthy"
            break
        fi
        sleep 2
    done
    
    if [[ "${status}" != "healthy" ]]; then
        echo "FAIL: ${tag} did not become healthy"
        docker logs healthcheck-test
        docker rm -f healthcheck-test
        exit 1
    fi
    
    docker rm -f healthcheck-test
done

echo "All images passed healthcheck verification"
```

**Verification**: All 4 images build successfully and transition to `healthy` status within 60 seconds.

---

## Phase 6: Validation & Sign-Off

### T6.1: Run docker compose config to validate zero errors after all changes

**File**: None (runtime verification)

**Requirements**: AC9

**Dependencies**: T1.1–T5.8

**Command**:

```bash
docker compose -f docker-compose.vps.yml config > /dev/null
```

**Verification**: Exit code 0, no stderr output.

---

### T6.2: Run post-fix brownfield scan against changed files

**File**: `docs/security/evidence/024-brownfield-postfix.txt` (new)

**Requirements**: AC10

**Dependencies**: T6.1

**Command**:

```bash
# Run the brownfield scan focusing on modified files
./scripts/ci-scan.sh --mode=brownfield --files \
  docker/admin-api/Dockerfile,docker/backup/Dockerfile,docker/ca-tool/Dockerfile,\
  docker/certbot-exporter/Dockerfile,tests/integration/test_end_to_end_call.py,\
  tests/integration/test_sip_trunk_failover.py,deploy/scripts/test-vps-local.sh,\
  deploy/scripts/vps-bootstrap.sh,deploy/scripts/vps-deploy.sh,\
  deploy/scripts/orchestrate-deploy.sh,deploy/scripts/safe-recovery.sh,\
  .env.example,docker-compose.vps.yml \
  > docs/security/evidence/024-brownfield-postfix.txt 2>&1
```

**Content** (template):

```text
# Post-Fix Brownfield Scan — Feature 024
# Date: 2026-05-24
# Scope: All files modified in Feature 024 remediation

## Scan Results

[Paste brownfield scan output here]

## Summary

- HIGH findings: <count>
- MEDIUM findings: <count>
- LOW findings: <count>
```

**Verification**: Scan output captured in file.

---

### T6.3: Verify zero HIGH/MEDIUM findings in scan results

**File**: None (runtime verification)

**Requirements**: AC10, SR-3

**Dependencies**: T6.2

**Command**:

```bash
#!/bin/bash
set -euo pipefail

HIGH_COUNT=$(grep -c "HIGH" docs/security/evidence/024-brownfield-postfix.txt || echo "0")
MEDIUM_COUNT=$(grep -c "MEDIUM" docs/security/evidence/024-brownfield-postfix.txt || echo "0")

if [[ "$HIGH_COUNT" -gt 0 || "$MEDIUM_COUNT" -gt 0 ]]; then
    echo "FAIL: Found ${HIGH_COUNT} HIGH and ${MEDIUM_COUNT} MEDIUM findings"
    exit 1
fi

echo "PASS: Zero HIGH/MEDIUM findings (SR-3 gate cleared)"
```

**Verification**: Script exits 0 with "PASS" message.

---

### T6.4: Run git diff to confirm no secrets in changes

**File**: `docs/security/evidence/024-git-diff.txt` (new)

**Requirements**: R1

**Dependencies**: T6.3

**Command**:

```bash
git diff --no-color > docs/security/evidence/024-git-diff.txt
```

**Verification**:
- `grep -E "password|secret|key|token" docs/security/evidence/024-git-diff.txt` shows only placeholder values (e.g., `change-me-to-a-long-random-string-32-chars`, `<REPLACE_ME>`).
- No actual credentials, API keys, or certificates are present in the diff.
- `secrets/` directory is not modified.

---

### T6.5: Write conventional commit with all Feature 024 changes

**File**: None (git operation)

**Requirements**: None (process task)

**Dependencies**: T6.4

**Command**:

```bash
git add -A
git commit -m "feat(024): brownfield remediation

- Pin admin-api Dockerfile to SHA256 digest (B1)
- Parameterize hard-coded IPs in integration tests (B2-B3)
- Add dynamic Docker network IP discovery to deploy scripts (B4-B6)
- Document all sleep statements with inline comments (B8-B10)
- Complete .env.example with all compose variables (B7)
- Verify OCP healthcheck behavior with userland-proxy=false (B11)
- Add/update HEALTHCHECK instructions in service Dockerfiles (B12)

Security:
- Trivy scan confirms zero new HIGH/CRITICAL CVEs (R2)
- No secrets committed in changes (R1)
- Dynamic IP discovery does not leak topology (R3)

Closes: B1-B12"
```

**Verification**: `git log -1 --oneline` shows the commit with conventional commit format.

---

### T6.6: Push commit to main and verify CI passes

**File**: None (git/CI operation)

**Requirements**: None (process task)

**Dependencies**: T6.5

**Command**:

```bash
git push origin main
```

**Verification**:
- Git push succeeds without conflicts.
- GitHub Actions workflow (if configured) completes with green status.
- No post-deploy verification failures.

---

## Checklist

- [ ] T1.1: Replace FROM line in docker/admin-api/Dockerfile with SHA-pinned php image
- [ ] T1.2: Add digest comment documenting fetch date and SHA verification command
- [ ] T1.3: Run docker build to verify image builds successfully
- [ ] T1.4: Run Trivy scan on pinned digest to verify no new HIGH/CRITICAL CVEs
- [ ] T1.5: Capture Trivy scan evidence in docs/security/evidence/024-trivy-scan.txt
- [ ] T2.1: Create get_test_ip() helper in tests/integration/test_end_to_end_call.py
- [ ] T2.2: Replace all hard-coded 172.x IPs in test_end_to_end_call.py with TEST_IP env var
- [ ] T2.3: Create get_test_ip() helper in tests/integration/test_sip_trunk_failover.py
- [ ] T2.4: Replace all hard-coded 172.x IPs in test_sip_trunk_failover.py with TEST_IP env var
- [ ] T2.5: Run pytest smoke test with TEST_IP=127.0.0.1 to verify parameterization
- [ ] T3.1: Replace static RTPENGINE_PRIVATE_IP in deploy/scripts/test-vps-local.sh with docker network inspect
- [ ] T3.2: Replace static RTPENGINE_INTERNAL_IP in deploy/scripts/test-vps-local.sh with docker network inspect
- [ ] T3.3: Replace static RTPENGINE_PRIVATE_IP in deploy/scripts/vps-bootstrap.sh with docker network inspect
- [ ] T3.4: Replace static RTPENGINE_INTERNAL_IP in deploy/scripts/vps-bootstrap.sh with docker network inspect
- [ ] T3.5: Replace static RTPENGINE_PRIVATE_IP in deploy/scripts/vps-deploy.sh with docker network inspect
- [ ] T3.6: Replace static RTPENGINE_INTERNAL_IP in deploy/scripts/vps-deploy.sh with docker network inspect
- [ ] T3.7: Add error handling: exit 1 with descriptive message if docker network inspect fails
- [ ] T3.8: Add inline comments before every sleep in deploy/scripts/orchestrate-deploy.sh explaining wait purpose
- [ ] T3.9: Add inline comments before every sleep in deploy/scripts/safe-recovery.sh explaining wait purpose
- [ ] T3.10: Add inline comments before every sleep in deploy/scripts/vps-deploy.sh explaining wait purpose
- [ ] T3.11: Verify with grep that all sleep statements in deploy/scripts/*.sh have preceding comments
- [ ] T4.1: Extract all variable references from docker-compose.vps.yml using grep
- [ ] T4.2: Audit existing .env.example and identify missing variables
- [ ] T4.3: Add all missing variables to .env.example with placeholder values and descriptive comments
- [ ] T4.4: Validate docker compose config with placeholder env values
- [ ] T5.1: Run docker compose exec ocp curl to verify current healthcheck behavior inside container namespace
- [ ] T5.2: If healthcheck fails with userland-proxy=false, update docker-compose.vps.yml healthcheck
- [X] T5.3: Add HEALTHCHECK to docker/admin-api/Dockerfile with 30s interval, 60s start_period, 3 retries
- [ ] T5.4: Add HEALTHCHECK to docker/backup/Dockerfile with file-based or command-based probe
- [X] T5.5: Add HEALTHCHECK to docker/anomaly-detector/Dockerfile with lightweight probe — **Already complete**
- [ ] T5.6: Add HEALTHCHECK to docker/ca-tool/Dockerfile with certificate validity check
- [ ] T5.7: Add HEALTHCHECK to docker/certbot-exporter/Dockerfile with metrics endpoint probe
- [ ] T5.8: Build each modified image and verify health status transitions to healthy within 60s
- [ ] T6.1: Run docker compose config to validate zero errors after all changes
- [ ] T6.2: Run post-fix brownfield scan against changed files
- [ ] T6.3: Verify zero HIGH/MEDIUM findings in scan results
- [ ] T6.4: Run git diff to confirm no secrets in changes
- [ ] T6.5: Write conventional commit with all Feature 024 changes
- [ ] T6.6: Push commit to main and verify CI passes
