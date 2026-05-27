<?php
/**
 * TSiSIP Control Panel — Logout
 */

require_once __DIR__ . '/common/config.php';

logAuditEvent('LOGOUT', 'ocp_user', $_SESSION['ocp_username'] ?? 'unknown', true);
logout();

header('Location: login.php');
exit;
