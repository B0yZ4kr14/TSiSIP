<?php
/**
 * TSiSIP — OpenSIPS MI Error Helper
 *
 * Provides human-friendly error messages for common MI failure modes.
 */

/**
 * Map a raw MI error message to a user-friendly localized string
 * and an optional remediation hint.
 *
 * @param string $rawError The raw error from miHttpCall
 * @return array{message: string, hint: string, severity: string}
 */
function miFriendlyError(string $rawError): array {
    $lower = strtolower($rawError);

    if (str_contains($lower, 'not exported') || str_contains($lower, 'unknown command') || str_contains($lower, 'not available')) {
        return [
            'message' => _('This OpenSIPS module or command is not available.'),
            'hint'    => _('The required module may not be loaded or compiled. Check your OpenSIPS configuration.'),
            'severity'=> 'warning',
        ];
    }

    if (str_contains($lower, 'module not initialized') || str_contains($lower, 'not initialized')) {
        return [
            'message' => _('The OpenSIPS module is not initialized.'),
            'hint'    => _('Ensure the module is loaded in opensips.cfg and required database tables exist.'),
            'severity'=> 'warning',
        ];
    }

    if (str_contains($lower, 'connection refused') || str_contains($lower, 'failed to connect')) {
        return [
            'message' => _('Unable to connect to the OpenSIPS Management Interface.'),
            'hint'    => _('Verify that OpenSIPS is running and the MI HTTP endpoint is accessible.'),
            'severity'=> 'error',
        ];
    }

    if (str_contains($lower, 'circuit breaker')) {
        return [
            'message' => _('OpenSIPS MI endpoint is temporarily unavailable due to repeated failures.'),
            'hint'    => _('The circuit breaker is open. Wait 60 seconds and retry.'),
            'severity'=> 'error',
        ];
    }

    if (str_contains($lower, 'no such file') || str_contains($lower, 'not found')) {
        return [
            'message' => _('The requested resource was not found on the OpenSIPS server.'),
            'hint'    => _('Verify the configuration and that the required module is active.'),
            'severity'=> 'warning',
        ];
    }

    if (str_contains($lower, 'permission denied') || str_contains($lower, 'unauthorized')) {
        return [
            'message' => _('Permission denied by OpenSIPS.'),
            'hint'    => _('Your role may not have sufficient privileges for this operation.'),
            'severity'=> 'error',
        ];
    }

    // Default fallback
    return [
        'message' => $rawError,
        'hint'    => _('If this persists, check the OpenSIPS logs and verify module configuration.'),
        'severity'=> 'error',
    ];
}

/**
 * Render a standardized MI error banner.
 *
 * @param string $rawError
 * @return string HTML snippet
 */
function miErrorBanner(string $rawError): string {
    $info = miFriendlyError($rawError);
    $severityClass = 'tsisip-alert-' . ($info['severity'] === 'warning' ? 'warning' : 'error');

    $html = '<div class="tsisip-alert ' . $severityClass . '">';
    $html .= '<strong>' . htmlspecialchars($info['message']) . '</strong>';
    if (!empty($info['hint'])) {
        $html .= '<br><small>' . htmlspecialchars($info['hint']) . '</small>';
    }
    $html .= '</div>';

    return $html;
}
