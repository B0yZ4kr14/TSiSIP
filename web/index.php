<?php
/**
 * TSiSIP Control Panel — Entry Point
 * Redirects authenticated users to dashboard, others to login.
 */
require_once __DIR__ . '/common/config.php';

if (!empty($_SESSION['ocp_user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
