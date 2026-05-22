<?php
/**
 * TSiSIP OCP — HA1 Hash Generator
 *
 * Generates HA1 hashes for SIP Digest authentication per RFC 3261 / RFC 8760.
 * OpenSIPS 3.6 LTS supports MD5, SHA-256, and SHA-512/256.
 */

/**
 * Generate HA1 hashes for a username/domain/password tuple.
 *
 * Returns an array with:
 *   ha1        => MD5(username:domain:password)
 *   ha1_sha256 => SHA-256(username:domain:password)
 *   ha1_sha512t256 => SHA-512/256(username:domain:password)
 */
function generateHa1Hashes(string $username, string $domain, string $password): array {
    $credentials = $username . ':' . $domain . ':' . $password;
    return [
        'ha1'            => md5($credentials),
        'ha1_sha256'     => hash('sha256', $credentials),
        'ha1_sha512t256' => hash('sha512/256', $credentials),
    ];
}
