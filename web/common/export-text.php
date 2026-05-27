<?php
/**
 * TSiSIP Control Panel — Text Export Utility
 * Provides plain-text export for tabular data.
 */

/**
 * Export an array of associative arrays as plain text.
 *
 * @param array  $rows    Array of associative arrays
 * @param array  $headers Column headers (key => label)
 * @param string $title   Export title
 * @return string
 */
function exportAsText(array $rows, array $headers, string $title = ''): string {
    $lines = [];
    if ($title !== '') {
        $lines[] = str_repeat('=', 80);
        $lines[] = $title;
        $lines[] = str_repeat('=', 80);
        $lines[] = '';
    }

    // Header row
    $headerValues = array_values($headers);
    $lines[] = implode(' | ', $headerValues);
    $lines[] = str_repeat('-', 80);

    // Data rows
    foreach ($rows as $row) {
        $values = [];
        foreach ($headers as $key => $label) {
            $val = $row[$key] ?? '';
            $val = str_replace(["\r", "\n", "\t"], [' ', ' ', ' '], (string)$val);
            $val = mb_strimwidth($val, 0, 40, '...');
            $values[] = $val;
        }
        $lines[] = implode(' | ', $values);
    }

    $lines[] = '';
    $lines[] = sprintf(_('Total: %d records'), count($rows));
    $lines[] = _('Generated: ') . date('Y-m-d H:i:s T');
    $lines[] = str_repeat('=', 80);

    return implode("\n", $lines) . "\n";
}

/**
 * Send text data as a downloadable file.
 *
 * @param string $content File content
 * @param string $filename Download filename
 */
function downloadText(string $content, string $filename): void {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}
