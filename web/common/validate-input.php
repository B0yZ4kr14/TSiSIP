<?php
/**
 * TSiSIP OCP — Input Validation Helper
 *
 * Reusable validation for length, charset, and SQL injection guards.
 * All functions return an empty string on success, or an error message on failure.
 */

/**
 * Validate a username for SIP subscriber creation.
 * Rules: 1-64 chars, alphanumeric + ._-
 */
function validateUsername(string $username): string {
    if ($username === '') {
        return _('Username is required.');
    }
    if (strlen($username) < 1 || strlen($username) > 64) {
        return _('Username must be between 1 and 64 characters.');
    }
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        return _('Username may only contain letters, numbers, dots, underscores, and hyphens.');
    }
    return '';
}

/**
 * Validate a SIP domain.
 * Rules: 1-255 chars, valid hostname characters
 */
function validateDomain(string $domain): string {
    if ($domain === '') {
        return _('Domain is required.');
    }
    if (strlen($domain) < 1 || strlen($domain) > 255) {
        return _('Domain must be between 1 and 255 characters.');
    }
    if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
        return _('Domain contains invalid characters.');
    }
    return '';
}

/**
 * Validate a password.
 * Rules: minimum 8 characters
 */
function validatePassword(string $password): string {
    if ($password === '') {
        return '';
    }
    if (strlen($password) < 8) {
        return _('Password must be at least 8 characters.');
    }
    return '';
}

/**
 * Validate an email address (optional field).
 */
function validateEmail(string $email): string {
    if ($email === '') {
        return '';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return _('Invalid email address.');
    }
    if (strlen($email) > 255) {
        return _('Email must not exceed 255 characters.');
    }
    return '';
}

/**
 * Validate a SIP destination (for dispatcher).
 * Rules: must start with sip: and be a valid URI
 */
function validateSipDestination(string $destination): string {
    if ($destination === '') {
        return _('Destination is required.');
    }
    if (!str_starts_with($destination, 'sip:')) {
        return _('Destination must start with sip:');
    }
    if (strlen($destination) > 255) {
        return _('Destination must not exceed 255 characters.');
    }
    return '';
}

/**
 * Sanitize a string for safe display.
 * Encodes HTML entities and strips control characters.
 */
function sanitizeDisplay(string $text): string {
    return htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8');
}
