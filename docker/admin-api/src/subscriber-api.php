<?php
/**
 * TSiSIP Admin API — Subscriber CRUD Endpoints
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit-logger.php';

function sendJson(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validateHex(string $value, int $expectedLength): bool {
    return ctype_xdigit($value) && strlen($value) === $expectedLength;
}

function validateInput(array $payload, array $required): array {
    $errors = [];
    foreach ($required as $field) {
        if (empty($payload[$field]) && $payload[$field] !== false && $payload[$field] !== 0) {
            $errors[] = "Field '{$field}' is required.";
        }
    }
    return $errors;
}

function handleSubscriberCreate(array $payload): void {
    $errors = validateInput($payload, ['username', 'domain', 'ha1', 'ha1_sha256', 'ha1_sha512t256', 'tenant_id']);
    if (!empty($errors)) {
        sendJson(['success' => false, 'errors' => $errors], 400);
    }

    // Reject plaintext passwords
    if (!empty($payload['password'])) {
        logProxyAudit('SUBSCRIBER_CREATE_REJECTED', 'subscriber', $payload['username'], false, ['reason' => 'plaintext_password']);
        sendJson(['success' => false, 'error' => 'Plaintext passwords are not accepted. Provide HA1 hashes only.'], 400);
    }

    // Validate HA1 formats
    if (!validateHex($payload['ha1'], 32) || !validateHex($payload['ha1_sha256'], 64) || !validateHex($payload['ha1_sha512t256'], 64)) {
        sendJson(['success' => false, 'error' => 'Invalid HA1 hash format.'], 400);
    }

    try {
        $pdo = getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO subscriber
             (username, domain, ha1, ha1_sha256, ha1_sha512t256, password, email_address, tenant_id, routing_group, enabled)
             VALUES (:username, :domain, :ha1, :ha1_sha256, :ha1_sha512t256, '', :email, :tenant_id, 1, :enabled)"
        );
        $stmt->execute([
            ':username'       => substr($payload['username'], 0, 64),
            ':domain'         => substr($payload['domain'], 0, 253),
            ':ha1'            => $payload['ha1'],
            ':ha1_sha256'     => $payload['ha1_sha256'],
            ':ha1_sha512t256' => $payload['ha1_sha512t256'],
            ':email'          => substr($payload['email'] ?? '', 0, 255),
            ':tenant_id'      => $payload['tenant_id'],
            ':enabled'        => $payload['enabled'] ?? true,
        ]);
        logProxyAudit('SUBSCRIBER_CREATE', 'subscriber', $payload['username'], true, [
            'domain'    => $payload['domain'],
            'tenant_id' => $payload['tenant_id'],
        ]);
        sendJson(['success' => true]);
    } catch (PDOException $e) {
        logProxyAudit('SUBSCRIBER_CREATE', 'subscriber', $payload['username'], false, ['error' => $e->getMessage()]);
        sendJson(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

function handleSubscriberUpdate(array $payload): void {
    $errors = validateInput($payload, ['id', 'username', 'domain', 'tenant_id']);
    if (!empty($errors)) {
        sendJson(['success' => false, 'errors' => $errors], 400);
    }

    try {
        $pdo = getPdo();
        if (!empty($payload['ha1'])) {
            if (!validateHex($payload['ha1'], 32) || !validateHex($payload['ha1_sha256'], 64) || !validateHex($payload['ha1_sha512t256'], 64)) {
                sendJson(['success' => false, 'error' => 'Invalid HA1 hash format.'], 400);
            }
            $stmt = $pdo->prepare(
                "UPDATE subscriber SET
                 username = :username, domain = :domain,
                 ha1 = :ha1, ha1_sha256 = :ha1_sha256, ha1_sha512t256 = :ha1_sha512t256,
                 email_address = :email, tenant_id = :tenant_id, enabled = :enabled, modified_at = NOW()
                 WHERE id = :id"
            );
            $stmt->execute([
                ':id'             => $payload['id'],
                ':username'       => substr($payload['username'], 0, 64),
                ':domain'         => substr($payload['domain'], 0, 253),
                ':ha1'            => $payload['ha1'],
                ':ha1_sha256'     => $payload['ha1_sha256'],
                ':ha1_sha512t256' => $payload['ha1_sha512t256'],
                ':email'          => substr($payload['email'] ?? '', 0, 255),
                ':tenant_id'      => $payload['tenant_id'],
                ':enabled'        => $payload['enabled'] ?? true,
            ]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE subscriber SET
                 username = :username, domain = :domain,
                 email_address = :email, tenant_id = :tenant_id, enabled = :enabled, modified_at = NOW()
                 WHERE id = :id"
            );
            $stmt->execute([
                ':id'        => $payload['id'],
                ':username'  => substr($payload['username'], 0, 64),
                ':domain'    => substr($payload['domain'], 0, 253),
                ':email'     => substr($payload['email'] ?? '', 0, 255),
                ':tenant_id' => $payload['tenant_id'],
                ':enabled'   => $payload['enabled'] ?? true,
            ]);
        }
        logProxyAudit('SUBSCRIBER_UPDATE', 'subscriber', $payload['id'], true, [
            'domain'    => $payload['domain'],
            'tenant_id' => $payload['tenant_id'],
        ]);
        sendJson(['success' => true]);
    } catch (PDOException $e) {
        logProxyAudit('SUBSCRIBER_UPDATE', 'subscriber', $payload['id'], false, ['error' => $e->getMessage()]);
        sendJson(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

function handleSubscriberDelete(array $payload): void {
    $errors = validateInput($payload, ['id']);
    if (!empty($errors)) {
        sendJson(['success' => false, 'errors' => $errors], 400);
    }

    try {
        $pdo = getPdo();
        $stmt = $pdo->prepare("DELETE FROM subscriber WHERE id = :id");
        $stmt->execute([':id' => $payload['id']]);
        logProxyAudit('SUBSCRIBER_DELETE', 'subscriber', $payload['id'], true);
        sendJson(['success' => true]);
    } catch (PDOException $e) {
        logProxyAudit('SUBSCRIBER_DELETE', 'subscriber', $payload['id'], false, ['error' => $e->getMessage()]);
        sendJson(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}
