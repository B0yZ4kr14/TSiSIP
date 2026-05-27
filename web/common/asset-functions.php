<?php
/**
 * TSiSIP Asset Helper Functions
 * Centralized to avoid duplication across header files.
 */

/**
 * Resolve a hashed asset path from the manifest.
 *
 * @param string $logicalName Logical asset name (e.g., 'tsisip-theme.css')
 * @param string $type        Asset type: 'css', 'js', or 'asset'
 * @return string Resolved public path
 */
function tsisip_asset(string $logicalName, string $type = 'css'): string {
    global $manifest;
    if (isset($manifest['assets'][$type][$logicalName])) {
        return 'tsisip/' . ($type === 'css' || $type === 'js' ? $type . '/' : 'assets/') . $manifest['assets'][$type][$logicalName];
    }
    // Fallback to unhashed path
    $fallbackDir = $type === 'css' || $type === 'js' ? $type . '/' : 'assets/';
    return 'tsisip/' . $fallbackDir . $logicalName;
}
