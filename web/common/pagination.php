<?php
/**
 * TSiSIP OCP — Pagination Helper
 *
 * Reusable pagination for database-backed list views.
 */

/**
 * Get LIMIT/OFFSET clause values for PDO queries.
 */
function getPagination(int $page, int $perPage = 25): array {
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;
    return ['limit' => $perPage, 'offset' => $offset];
}

/**
 * Render pagination controls.
 */
function renderPagination(int|string $currentPage, int|string $totalItems, int|string $perPage = 25, string $baseUrl = ''): string {
    $currentPage = (int) $currentPage;
    $totalItems = (int) $totalItems;
    $perPage = (int) $perPage;
    $totalPages = (int) ceil($totalItems / $perPage);
    if ($totalPages <= 1) {
        return '';
    }
    $currentPage = max(1, min($currentPage, $totalPages));
    $html = '<nav class="tsisip-pagination" aria-label="' . _('Pagination') . '">\n';
    $html .= '  <div class="tsisip-pagination-info">' . sprintf(_('Page %d of %d (%d items)'), $currentPage, $totalPages, $totalItems) . '</div>\n';
    $html .= '  <div class="tsisip-pagination-links">\n';
    
    // Previous
    if ($currentPage > 1) {
        $html .= '    <a href="' . htmlspecialchars($baseUrl . '?page=' . ($currentPage - 1), ENT_QUOTES) . '" class="tsisip-btn tsisip-btn-secondary">« ' . _('Previous') . '</a>\n';
    } else {
        $html .= '    <span class="tsisip-btn tsisip-btn-secondary" disabled>« ' . _('Previous') . '</span>\n';
    }
    
    // Page numbers (simplified)
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= '    <span class="tsisip-btn tsisip-btn-primary">' . $i . '</span>\n';
        } else {
            $html .= '    <a href="' . htmlspecialchars($baseUrl . '?page=' . $i, ENT_QUOTES) . '" class="tsisip-btn tsisip-btn-secondary">' . $i . '</a>\n';
        }
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $html .= '    <a href="' . htmlspecialchars($baseUrl . '?page=' . ($currentPage + 1), ENT_QUOTES) . '" class="tsisip-btn tsisip-btn-secondary">' . _('Next') . ' »</a>\n';
    } else {
        $html .= '    <span class="tsisip-btn tsisip-btn-secondary" disabled>' . _('Next') . ' »</span>\n';
    }
    
    $html .= '  </div>\n</nav>';
    return $html;
}
