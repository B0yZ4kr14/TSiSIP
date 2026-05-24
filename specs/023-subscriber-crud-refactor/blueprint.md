# Blueprint: Feature 023 — Subscriber CRUD Refactor

**Branch**: `main` | **Date**: 2026-05-24
**Mode**: doc-only
**Total Tasks**: 28 | **Files**: 2 new, 2 modified, 0 deleted
**Approach**: Option A (OpenSIPS MI Command) — recommended for constitution alignment

---

## Key Decisions

| Decision | Impact | Task Ref |
|---|---|---|
| **AD-1: OpenSIPS MI Command** | Proxy implemented as custom MI commands in OpenSIPS config; OCP calls existing MI HTTP interface. No new containers. | T0.5, T1.1 |
| **AD-2: HA1 Generation Stays in OCP** | `generateHa1Hashes()` remains in OCP; precomputed hashes passed to MI commands. No plaintext passwords over the wire. | T2.2, T2.3 |
| **AD-3: Internal Network + Shared Secret** | OCP→OpenSIPS MI uses internal `sip_internal` network; auth via Docker secret shared between services. | T1.2, T2.2 |

## Implementation Order

```
T0.1 (Security assessment)
T0.2 (Threat model)
T0.3 (Evidence index)
T0.4 (MSL review)
T0.5 (ADR — MI vs REST)
T0.6 (Secure-dev scan)
    ↓
T1.1 (MI command handlers)
T1.2 (Proxy auth)
T1.3 (Rate limiting)
T1.4 (Audit logging)
T1.5 (Negative test — plaintext rejection)
T1.6 (Positive test — HA1 acceptance)
    ↓
T2.1 (Remove direct writes)
T2.2 (Proxy client helper)
T2.3 (Integrate create)
T2.4 (Integrate update)
T2.5 (Integrate delete)
T2.6 (Preserve RBAC)
T2.7 (Graceful fallback)
T2.8 (Regression test)
T2.9 (Secret-leakage scan)
T2.10 (CSRF validation)
    ↓
T3.1–T3.6 (Validation & closure)
```

---

## Phase 0: Security Foundation

### T0.1: Create Security Assessment

**File**: `docs/security/023-subscriber-crud-refactor-security-assessment.md` (new)

**Requirements**: R1–R10

Write the security assessment document covering data classification, proxy auth model, input validation, and rate limiting design.

**Verification**: Document exists and covers all 10 security requirements.

---

### T0.2: Create Threat Model

**File**: `docs/security/023-subscriber-crud-refactor-threat-model.md` (new)

**Requirements**: R1–R10

Write STRIDE threat model covering proxy injection, hash tampering, and unauthorized subscriber modification.

**Verification**: Document exists and covers STRIDE for all three threat categories.

---

### T0.3: Update Security Evidence Index

**File**: `docs/security/008-security-evidence-index.md` (modify)

**Before** (append to existing Feature 020 entries):
```markdown
| Feature 020 | 2026-05-21 | OCP Critical Tool Gap Closure | docs/security/020-ocp-gap-closure-security-assessment.md | PASS |
```

**After**:
```markdown
| Feature 020 | 2026-05-21 | OCP Critical Tool Gap Closure | docs/security/020-ocp-gap-closure-security-assessment.md | PASS |
| Feature 023 | 2026-05-24 | Subscriber CRUD Refactor | docs/security/023-subscriber-crud-refactor-security-assessment.md | PENDING |
```

**Verification**: Feature 023 appears in the index with correct paths.

---

### T0.4: MSL Applicability Review

**File**: `docs/security/023-subscriber-crud-refactor-msl.md` (new)

Document MSL applicability justification for subscriber HA1 data.

**Verification**: Document justifies why HA1 hashes are sensitive and how proxy layer mitigates risk.

---

### T0.5: Architecture Decision Record

**File**: `docs/architecture/023-adr-subscriber-proxy.md` (new)

**Requirements**: AC1

Document the ADR choosing Option A (MI Command) with trade-off analysis.

**Verification**: ADR approved and committed.

---

### T0.6: Secure-Development Verification

**File**: — (scan task)

Run secure-development scan on existing `web/subscribers.php` to establish baseline before refactoring.

**Verification**: Scan report shows zero SQL injection, XSS, or secret leakage in baseline.

---

## Phase 1: Proxy Layer Implementation

### T1.1: Implement MI Command Handlers

**File**: `opensips/opensips.cfg.tpl` (modify)

**Requirements**: AC2, R2, R3

**Dependencies**: T0.5

Add custom MI commands to OpenSIPS config. The `mi_http` module exposes commands via HTTP. We add `subscriber_create`, `subscriber_update`, `subscriber_delete` as custom MI commands using the `sql_query` module.

**Before**: No subscriber management MI commands exist.

**After**: Add the following to `opensips/opensips.cfg.tpl` in the module loading section:

```cfg
# --- Subscriber Management MI Commands (Feature 023) ---
# Requires sql_query module loaded
modparam("sql_query", "connection", "dbconn")
```

Add route logic for MI command handling (inserted after the existing `route[REQUEST_INIT]` or in a dedicated subscriber management section):

```cfg
# MI command: subscriber_create
# Parameters: username, domain, ha1, ha1_sha256, ha1_sha512t256, email, tenant_id, enabled
route[MI_SUBSCRIBER_CREATE] {
    sql_query("dbconn",
        "INSERT INTO subscriber
         (username, domain, ha1, ha1_sha256, ha1_sha512t256, password, email_address, tenant_id, routing_group, enabled)
         VALUES ($var(username), $var(domain), $var(ha1), $var(ha1_sha256), $var(ha1_sha512t256), '', $var(email), $var(tenant_id), 1, $var(enabled))",
        $var(rows));
    if ($var(rows) > 0) {
        xlog("L_INFO", "Subscriber created: $var(username)@$var(domain)");
    } else {
        xlog("L_ERR", "Failed to create subscriber: $var(username)@$var(domain)");
        send_reply("500", "Internal Server Error");
    }
}

# MI command: subscriber_update
# Parameters: id, username, domain, ha1, ha1_sha256, ha1_sha512t256, email, tenant_id, enabled
route[MI_SUBSCRIBER_UPDATE] {
    if ($var(ha1) != "") {
        sql_query("dbconn",
            "UPDATE subscriber SET
             username = $var(username), domain = $var(domain),
             ha1 = $var(ha1), ha1_sha256 = $var(ha1_sha256), ha1_sha512t256 = $var(ha1_sha512t256),
             email_address = $var(email), tenant_id = $var(tenant_id), enabled = $var(enabled), modified_at = NOW()
             WHERE id = $var(id)",
            $var(rows));
    } else {
        sql_query("dbconn",
            "UPDATE subscriber SET
             username = $var(username), domain = $var(domain),
             email_address = $var(email), tenant_id = $var(tenant_id), enabled = $var(enabled), modified_at = NOW()
             WHERE id = $var(id)",
            $var(rows));
    }
    if ($var(rows) > 0) {
        xlog("L_INFO", "Subscriber updated: id=$var(id)");
    } else {
        xlog("L_ERR", "Failed to update subscriber: id=$var(id)");
        send_reply("500", "Internal Server Error");
    }
}

# MI command: subscriber_delete
# Parameters: id
route[MI_SUBSCRIBER_DELETE] {
    sql_query("dbconn", "DELETE FROM subscriber WHERE id = $var(id)", $var(rows));
    if ($var(rows) > 0) {
        xlog("L_INFO", "Subscriber deleted: id=$var(id)");
    } else {
        xlog("L_ERR", "Failed to delete subscriber: id=$var(id)");
        send_reply("500", "Internal Server Error");
    }
}
```

**Verification**: `opensips -c` validates the config after envsubst.

---

### T1.2: Implement Proxy Authentication

**File**: `opensips/opensips.cfg.tpl` (modify), `docker-compose.yml` (modify)

**Requirements**: R1, R7

**Dependencies**: T1.1

Add service secret validation. The MI HTTP interface runs on the internal network only. Add a shared secret header check.

**Before**: MI HTTP accepts requests from internal network without additional auth.

**After**: Add to `opensips/opensips.cfg.tpl`:

```cfg
# Validate X-Proxy-Secret header on subscriber management MI commands
route[CHECK_PROXY_SECRET] {
    if ($hdr(X-Proxy-Secret) != "$var(proxy_secret)") {
        xlog("L_WARN", "Unauthorized subscriber management attempt from $si");
        send_reply("403", "Forbidden");
        exit;
    }
}
```

Add to `docker-compose.yml` under the `opensips` service:

```yaml
    secrets:
      - db_password
      - auth_secret
      - topology_secret
      - proxy_api_secret  # NEW: Feature 023
```

Add to `docker-compose.yml` at the top level:

```yaml
secrets:
  proxy_api_secret:
    file: ./secrets/proxy_api_secret
```

**Verification**: Requests without `X-Proxy-Secret` header receive 403.

---

### T1.3: Implement Rate Limiting

**File**: `opensips/opensips.cfg.tpl` (modify)

**Requirements**: R5

**Dependencies**: T1.1

Add rate limiting using OpenSIPS `pike` module or `htable` counters.

**After**: Add to `opensips/opensips.cfg.tpl`:

```cfg
# Rate limiting: max 10 subscriber creations per minute per source IP
modparam("htable", "htable", "subscriber_rate=>size=8|autoexpire=60")

route[CHECK_SUBSCRIBER_RATE] {
    $var(key) = "create_" + $si;
    if ($sht(subscriber_rate=>$var(key)) == $null) {
        $sht(subscriber_rate=>$var(key)) = 1;
    } else {
        $sht(subscriber_rate=>$var(key)) = $sht(subscriber_rate=>$var(key)) + 1;
    }
    if ($sht(subscriber_rate=>$var(key)) > 10) {
        xlog("L_WARN", "Rate limit exceeded for subscriber creation from $si");
        send_reply("429", "Too Many Requests");
        exit;
    }
}
```

**Verification**: 11th creation request within 60 seconds from same IP returns 429.

---

### T1.4: Implement Audit Logging on Proxy Layer

**File**: `opensips/opensips.cfg.tpl` (modify)

**Requirements**: R4, AC6

**Dependencies**: T1.1

Add `auth_audit_log` INSERT after each successful MI command.

**After**: Append to each subscriber route:

```cfg
    # Audit logging
    sql_query("dbconn",
        "INSERT INTO auth_audit_log (event_time, action, resource_type, resource_id, user_id, result, details)
         VALUES (NOW(), 'SUBSCRIBER_CREATE', 'subscriber', $var(username), 'opensips-mi', true, json_build_object('domain', $var(domain), 'tenant_id', $var(tenant_id)))",
        $var(audit_rows));
```

(Similar for UPDATE and DELETE with appropriate action names.)

**Verification**: `auth_audit_log` contains entries for all subscriber mutations with `user_id = 'opensips-mi'`.

---

### T1.5: Negative Test — Plaintext Password Rejection

**File**: — (test task)

**Requirements**: R3

**Verification**: If proxy receives plaintext password instead of HA1 hashes, reject with 400 Bad Request.

---

### T1.6: Positive Test — HA1 Acceptance

**File**: — (test task)

**Requirements**: AC2

**Verification**: Valid HA1 hashes passed to MI command result in successful database write.

---

## Phase 2: OCP Migration

### T2.1: Remove Direct Subscriber Writes

**File**: `web/subscribers.php` (modify)

**Requirements**: AC3, AC4, R6

**Dependencies**: T1.1, T1.2

Remove all direct `INSERT INTO subscriber`, `UPDATE subscriber`, and `DELETE FROM subscriber` statements. Changes applied bottom-to-top to preserve line numbers.

**Before** (line 145–156, `delete` action):
```php
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if ($id !== '') {
                try {
                    $stmt = $pdo->prepare("DELETE FROM subscriber WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $success = _('Subscriber deleted successfully.');
                    logAuditEvent('SUBSCRIBER_DELETE', 'subscriber', $id, true);
                } catch (PDOException $e) {
                    $error = _('Failed to delete subscriber: ') . $e->getMessage();
                }
            }
        }
```

**After**:
```php
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if ($id !== '') {
                $result = callSubscriberProxy('delete', ['id' => $id]);
                if ($result['success']) {
                    $success = _('Subscriber deleted successfully.');
                } else {
                    $error = $result['error'] ?? _('Failed to delete subscriber.');
                }
            }
        }
```

**Before** (line 134–144, `toggle` action):
```php
        } elseif ($action === 'toggle') {
            $id = $_POST['id'] ?? '';
            $enabled = $_POST['enabled'] ?? '0';
            if ($id !== '') {
                $stmt = $pdo->prepare("UPDATE subscriber SET enabled = :enabled, modified_at = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id, ':enabled' => ($enabled === '1' ? true : false)]);
                $success = _('Subscriber status updated.');
                logAuditEvent('SUBSCRIBER_TOGGLE', 'subscriber', $id, true, [
                    'enabled' => ($enabled === '1' ? true : false),
                ]);
            }
        }
```

**After**:
```php
        } elseif ($action === 'toggle') {
            $id = $_POST['id'] ?? '';
            $enabled = $_POST['enabled'] ?? '0';
            if ($id !== '') {
                $result = callSubscriberProxy('update', [
                    'id'      => $id,
                    'enabled' => ($enabled === '1' ? true : false),
                ]);
                if ($result['success']) {
                    $success = _('Subscriber status updated.');
                } else {
                    $error = $result['error'] ?? _('Failed to update subscriber status.');
                }
            }
        }
```

**Before** (line 66–133, `update` action):
```php
        } elseif ($action === 'update') {
            $id       = $_POST['id'] ?? '';
            $username = trim($_POST['username'] ?? '');
            $domain   = trim($_POST['domain'] ?? '');
            $password = $_POST['password'] ?? '';
            $tenantId = $_POST['tenant_id'] ?? '00000000-0000-0000-0000-000000000000';
            $enabled  = isset($_POST['enabled']) ? true : false;

            if ($id === '' || $username === '' || $domain === '') {
                $error = _('ID, username, and domain are required.');
            } else {
                try {
                    if ($password !== '') {
                        $hashes = generateHa1Hashes($username, $domain, $password);
                        $stmt = $pdo->prepare(
                            "UPDATE subscriber SET
                             username = :username,
                             domain = :domain,
                             ha1 = :ha1,
                             ha1_sha256 = :ha1_sha256,
                             ha1_sha512t256 = :ha1_sha512t256,
                             email_address = :email,
                             tenant_id = :tenant_id,
                             enabled = :enabled,
                             modified_at = NOW()
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            ':id'            => $id,
                            ':username'      => $username,
                            ':domain'        => $domain,
                            ':ha1'           => $hashes['ha1'],
                            ':ha1_sha256'    => $hashes['ha1_sha256'],
                            ':ha1_sha512t256'=> $hashes['ha1_sha512t256'],
                            ':email'         => $_POST['email'] ?? '',
                            ':tenant_id'     => $tenantId,
                            ':enabled'       => $enabled,
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            "UPDATE subscriber SET
                             username = :username,
                             domain = :domain,
                             email_address = :email,
                             tenant_id = :tenant_id,
                             enabled = :enabled,
                             modified_at = NOW()
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            ':id'        => $id,
                            ':username'  => $username,
                            ':domain'    => $domain,
                            ':email'     => $_POST['email'] ?? '',
                            ':tenant_id' => $tenantId,
                            ':enabled'   => $enabled,
                        ]);
                    }
                    $success = _('Subscriber updated successfully.');
                    logAuditEvent('SUBSCRIBER_UPDATE', 'subscriber', $username, true, [
                        'domain'    => $domain,
                        'tenant_id' => $tenantId,
                        'enabled'   => $enabled,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to update subscriber: ') . $e->getMessage();
                }
            }
        }
```

**After**:
```php
        } elseif ($action === 'update') {
            $id       = $_POST['id'] ?? '';
            $username = trim($_POST['username'] ?? '');
            $domain   = trim($_POST['domain'] ?? '');
            $password = $_POST['password'] ?? '';
            $tenantId = $_POST['tenant_id'] ?? '00000000-0000-0000-0000-000000000000';
            $enabled  = isset($_POST['enabled']) ? true : false;

            if ($id === '' || $username === '' || $domain === '') {
                $error = _('ID, username, and domain are required.');
            } else {
                $params = [
                    'id'        => $id,
                    'username'  => $username,
                    'domain'    => $domain,
                    'email'     => $_POST['email'] ?? '',
                    'tenant_id' => $tenantId,
                    'enabled'   => $enabled,
                ];
                if ($password !== '') {
                    $params['hashes'] = generateHa1Hashes($username, $domain, $password);
                }
                $result = callSubscriberProxy('update', $params);
                if ($result['success']) {
                    $success = _('Subscriber updated successfully.');
                } else {
                    $error = $result['error'] ?? _('Failed to update subscriber.');
                }
            }
        }
```

**Before** (line 27–65, `create` action):
```php
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $domain   = trim($_POST['domain'] ?? '');
            $password = $_POST['password'] ?? '';
            $tenantId = $_POST['tenant_id'] ?? '00000000-0000-0000-0000-000000000000';
            $enabled  = isset($_POST['enabled']) ? true : false;

            if ($username === '' || $domain === '' || $password === '') {
                $error = _('Username, domain, and password are required.');
            } elseif (strlen($password) < 8) {
                $error = _('Password must be at least 8 characters.');
            } else {
                $hashes = generateHa1Hashes($username, $domain, $password);
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO subscriber
                         (username, domain, ha1, ha1_sha256, ha1_sha512t256, password, email_address, tenant_id, routing_group, enabled)
                         VALUES (:username, :domain, :ha1, :ha1_sha256, :ha1_sha512t256, '', :email, :tenant_id, 1, :enabled)"
                    );
                    $stmt->execute([
                        ':username'      => $username,
                        ':domain'        => $domain,
                        ':ha1'           => $hashes['ha1'],
                        ':ha1_sha256'    => $hashes['ha1_sha256'],
                        ':ha1_sha512t256'=> $hashes['ha1_sha512t256'],
                        ':email'         => $_POST['email'] ?? '',
                        ':tenant_id'     => $tenantId,
                        ':enabled'       => $enabled,
                    ]);
                    $success = _('Subscriber created successfully.');
                    logAuditEvent('SUBSCRIBER_CREATE', 'subscriber', $username, true, [
                        'domain'    => $domain,
                        'tenant_id' => $tenantId,
                        'enabled'   => $enabled,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to create subscriber: ') . $e->getMessage();
                }
            }
        }
```

**After**:
```php
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $domain   = trim($_POST['domain'] ?? '');
            $password = $_POST['password'] ?? '';
            $tenantId = $_POST['tenant_id'] ?? '00000000-0000-0000-0000-000000000000';
            $enabled  = isset($_POST['enabled']) ? true : false;

            if ($username === '' || $domain === '' || $password === '') {
                $error = _('Username, domain, and password are required.');
            } elseif (strlen($password) < 8) {
                $error = _('Password must be at least 8 characters.');
            } else {
                $hashes = generateHa1Hashes($username, $domain, $password);
                $result = callSubscriberProxy('create', [
                    'username'  => $username,
                    'domain'    => $domain,
                    'hashes'    => $hashes,
                    'email'     => $_POST['email'] ?? '',
                    'tenant_id' => $tenantId,
                    'enabled'   => $enabled,
                ]);
                if ($result['success']) {
                    $success = _('Subscriber created successfully.');
                } else {
                    $error = $result['error'] ?? _('Failed to create subscriber.');
                }
            }
        }
```

**Verification**: `grep -c "INSERT INTO subscriber\|UPDATE subscriber\|DELETE FROM subscriber" web/subscribers.php` returns 0.

---

### T2.2: Create Proxy Client Helper

**File**: `web/common/subscriber-proxy.php` (new)

**Requirements**: AC4, AC5, R7, R8

**Dependencies**: T1.1, T1.2

Complete implementation of the OCP-to-OpenSIPS MI proxy client.

```php
<?php
/**
 * TSiSIP OCP — Subscriber Proxy Client
 *
 * Delegates subscriber CREATE/UPDATE/DELETE to the OpenSIPS MI layer.
 * Communicates over the internal Docker network.
 */

require_once __DIR__ . '/config.php';

/**
 * Call the subscriber proxy (OpenSIPS MI HTTP) to perform a mutation.
 *
 * @param string $action   One of: create, update, delete
 * @param array  $params   Action-specific parameters
 * @return array           ['success' => bool, 'error' => ?string]
 */
function callSubscriberProxy(string $action, array $params): array {
    $miEndpoint = getenv('OPENSIPS_MI_URL') ?: 'http://opensips:8888/mi';
    $proxySecret = file_get_contents('/run/secrets/proxy_api_secret');

    $payload = [
        'jsonrpc' => '2.0',
        'method'  => 'subscriber_' . $action,
        'params'  => [$params],
        'id'      => uniqid('sub_', true),
    ];

    $ch = curl_init($miEndpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Proxy-Secret: ' . $proxySecret,
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '') {
        return [
            'success' => false,
            'error'   => _('Subscriber service temporarily unavailable. Please try again later.'),
        ];
    }

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error'   => _('Subscriber operation failed. HTTP ') . $httpCode,
        ];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error'   => _('Invalid response from subscriber service.'),
        ];
    }

    if (isset($decoded['error'])) {
        return [
            'success' => false,
            'error'   => $decoded['error']['message'] ?? _('Subscriber operation failed.'),
        ];
    }

    return ['success' => true, 'error' => null];
}
```

**Verification**: Client returns `['success' => true]` on valid requests and sanitized errors on failure.

---

### T2.3–T2.5: Integrate Proxy Client

**File**: `web/subscribers.php` (modify)

**Requirements**: AC4, AC5

**Dependencies**: T2.1, T2.2

Already covered in T2.1 changes above. The `callSubscriberProxy()` calls are inserted into create, update, and delete flows.

**Verification**: End-to-end test: create subscriber via OCP → verify row exists in `subscriber` → update → verify changes → delete → verify row removed.

---

### T2.6: Preserve Role-Based Access

**File**: `web/subscribers.php` (no change needed)

**Requirements**: R9, AC9

Existing `requireRole('devops')` at line 14 and admin checks in create/update/delete are preserved. No modifications required.

**Verification**: Non-devops users cannot access subscribers.php; non-admin users cannot mutate (enforced by form availability or proxy-side checks).

---

### T2.7: Graceful Fallback

**File**: `web/common/subscriber-proxy.php` (already implemented in T2.2)

**Requirements**: R8, AC8

The `callSubscriberProxy()` function handles:
- cURL timeout → user-friendly error
- HTTP non-200 → sanitized error without stack trace
- Invalid JSON → generic error message
- MI error → forwarded message (already sanitized by OpenSIPS)

**Verification**: Block MI HTTP endpoint; attempt subscriber create; verify OCP shows friendly error without internal paths.

---

### T2.8: Regression Test

**File**: — (test task)

**Requirements**: R10, AC10

Verify existing functionality:
1. Subscriber list, search, pagination unchanged
2. Create, update, delete work through proxy
3. Audit log entries present for all operations

**Verification**: Manual or automated test script passes all assertions.

---

### T2.9: Secret-Leakage Scan

**File**: — (scan task)

Scan `web/common/subscriber-proxy.php` and modified `web/subscribers.php` for plaintext secrets.

**Verification**: `grep -r "password.*=.*['\"][^'\"]\{5,\}['\"]" web/common/subscriber-proxy.php web/subscribers.php` returns 0.

---

### T2.10: CSRF Validation Test

**File**: — (test task)

Verify CSRF token validation still enforced on all mutating forms after proxy integration.

**Verification**: Attempt POST without `csrf_token`; verify 403 rejection.

---

## Phase 2.5: Security Validation

### T2.5a–T2.5d: Security Scans

Already covered in T2.9 and T2.10. Additional scans:

- **SQL injection**: Verify `callSubscriberProxy()` does not concatenate SQL
- **XSS**: Verify proxy error messages are wrapped in `htmlspecialchars()` before display

---

## Phase 3: Validation & Closure

### T3.1: Spec Validation

Run `speckit.spec-validate.validate` on Feature 023 spec.

**Verification**: All 10 ACs verified complete.

---

### T3.2: Architecture-Guard Verification

**File**: — (validation task)

Confirm ARCH-PRE-001 resolved.

**Verification**: `grep -c "INSERT INTO subscriber\|UPDATE subscriber\|DELETE FROM subscriber" web/subscribers.php` returns 0.

---

### T3.3: Brownfield Scan

Run brownfield scan against canonical spec and AGENTS.md.

**Verification**: Zero drift, zero rejected patterns.

---

### T3.4: Update Architecture Constitution

**File**: `.specify/memory/architecture_constitution.md` (modify)

**Before**:
```markdown
| ARCH-PRE-001 | web/subscribers.php | OCP writes to `subscriber` table (INSERT/UPDATE) | Feature 012 | Tracked; requires refactor to move subscriber writes to OpenSIPS layer or dedicated API |
```

**After**:
```markdown
| ARCH-PRE-001 | web/subscribers.php | OCP writes to `subscriber` table (INSERT/UPDATE) | Feature 012 | **Resolved** by Feature 023 — subscriber mutations now route through OpenSIPS MI commands |
```

**Verification**: `grep "ARCH-PRE-001" .specify/memory/architecture_constitution.md` shows "Resolved".

---

### T3.5: Conventional Commit and Push

Write conventional commit and push to `main`.

---

### T3.6: Update Operator Runbook

**File**: `docs/TSiSIP-OPERATOR-RUNBOOK.md` (modify)

Add section for subscriber management via proxy layer, including troubleshooting MI command failures.

---

## Checklist

- [ ] T0.1: Create security assessment
- [ ] T0.2: Create threat model
- [ ] T0.3: Update security evidence index
- [ ] T0.4: MSL applicability review
- [ ] T0.5: Write ADR (MI vs REST)
- [ ] T0.6: Secure-development verification
- [ ] T1.1: Implement MI command handlers
- [ ] T1.2: Implement proxy authentication
- [ ] T1.3: Implement rate limiting
- [ ] T1.4: Implement audit logging on proxy layer
- [ ] T1.5: Negative test — plaintext password rejection
- [ ] T1.6: Positive test — HA1 acceptance
- [ ] T2.1: Remove direct subscriber writes from subscribers.php
- [ ] T2.2: Create web/common/subscriber-proxy.php
- [ ] T2.3: Integrate proxy client into creation flow
- [ ] T2.4: Integrate proxy client into update flow
- [ ] T2.5: Integrate proxy client into delete flow
- [ ] T2.6: Preserve role-based access
- [ ] T2.7: Implement graceful fallback
- [ ] T2.8: Regression test
- [ ] T2.9: Secret-leakage scan
- [ ] T2.10: CSRF validation test
- [ ] T3.1: Spec validation
- [ ] T3.2: Architecture-guard verification
- [ ] T3.3: Brownfield scan
- [ ] T3.4: Update architecture_constitution.md
- [ ] T3.5: Conventional commit and push
- [ ] T3.6: Update operator runbook
