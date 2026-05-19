<?php
/**
 * TSiSIP Control Panel — Wiki Engine
 * Markdown renderer with TOC generation and role-based access.
 */
session_start();
require_once __DIR__ . '/common/header.php';

$wikiDir     = __DIR__ . '/../docs/wiki/';
$defaultPage = 'README.md';

$page = isset($_GET['page']) ? basename($_GET['page']) : $defaultPage;
if ($page === '') {
    $page = $defaultPage;
}
if (substr($page, -3) !== '.md') {
    $page .= '.md';
}

$filePath = $wikiDir . $page;
$found    = file_exists($filePath) && is_file($filePath) && is_readable($filePath);

$content   = '';
$toc       = [];
$pageTitle = _('Wiki');

if ($found) {
    $raw = file_get_contents($filePath);
    if ($raw !== false) {
        [$content, $toc] = renderMarkdown($raw);
        if (preg_match('/^#\s+(.+)$/m', $raw, $m)) {
            $pageTitle = trim($m[1]);
        }
    }
}

/**
 * Simple regex-based markdown parser.
 *
 * @param string $text Raw markdown.
 * @return array [html string, toc array]
 */
function renderMarkdown(string $text): array {
    $toc     = [];
    $html    = '';
    $lines   = explode("\n", $text);
    $inCode  = false;
    $codeBuf = [];
    $inList  = false;
    $listBuf = [];
    $inTable = false;
    $tblHead = '';
    $tblBody = '';

    foreach ($lines as $line) {
        // Code blocks
        if (preg_match('/^```/', $line)) {
            if ($inCode) {
                $html .= '<pre><code>' . htmlspecialchars(implode("\n", $codeBuf), ENT_QUOTES, 'UTF-8') . '</code></pre>' . "\n";
                $codeBuf = [];
                $inCode  = false;
            } else {
                flushTable($html, $inTable, $tblHead, $tblBody);
                flushList($html, $inList, $listBuf);
                $inCode = true;
            }
            continue;
        }

        if ($inCode) {
            $codeBuf[] = $line;
            continue;
        }

        // Horizontal rule
        if (preg_match('/^(---+|___+|\*\*\*+)\s*$/', $line)) {
            flushTable($html, $inTable, $tblHead, $tblBody);
            flushList($html, $inList, $listBuf);
            $html .= '<hr>' . "\n";
            continue;
        }

        // Headers
        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
            flushTable($html, $inTable, $tblHead, $tblBody);
            flushList($html, $inList, $listBuf);
            $level = strlen($m[1]);
            $title = trim($m[2]);
            $anchor = 'toc-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
            $anchor = trim($anchor, '-');
            if ($anchor === '') {
                $anchor = 'toc-' . substr(md5($title), 0, 8);
            }
            $html .= "<h{$level} id=\"" . htmlspecialchars($anchor, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h{$level}>\n";
            if ($level === 2 || $level === 3) {
                $toc[] = ['level' => $level, 'title' => $title, 'anchor' => $anchor];
            }
            continue;
        }

        // Tables
        if (preg_match('/^\|(.+)\|$/', $line)) {
            flushList($html, $inList, $listBuf);
            $cells = array_map('trim', explode('|', trim($line, '|')));
            // Skip separator rows
            if (preg_match('/^[\s\-:|]+$/', trim($line, '|'))) {
                continue;
            }
            $row = '<tr>';
            foreach ($cells as $cell) {
                $row .= '<td>' . inlineMarkdown($cell) . '</td>';
            }
            $row .= '</tr>';
            if (!$inTable) {
                $inTable = true;
                $tblHead = $row;
                $tblBody = '';
            } else {
                $tblBody .= $row . "\n";
            }
            continue;
        } else {
            flushTable($html, $inTable, $tblHead, $tblBody);
        }

        // Lists
        if (preg_match('/^(\s*)([*+-]|\d+\.)\s+(.+)$/', $line, $m)) {
            if (!$inList) {
                $inList = true;
                $listBuf = [];
            }
            $listBuf[] = '<li>' . inlineMarkdown($m[3]) . '</li>';
            continue;
        } elseif ($inList && trim($line) === '') {
            flushList($html, $inList, $listBuf);
            continue;
        } elseif ($inList) {
            $listBuf[] = '<li>' . inlineMarkdown($line) . '</li>';
            continue;
        }

        // Blockquote
        if (preg_match('/^>\s?(.+)$/', $line, $m)) {
            $html .= '<blockquote>' . inlineMarkdown($m[1]) . '</blockquote>' . "\n";
            continue;
        }

        // Empty line vs paragraph
        if (trim($line) === '') {
            $html .= "\n";
        } else {
            $html .= '<p>' . inlineMarkdown($line) . '</p>' . "\n";
        }
    }

    flushTable($html, $inTable, $tblHead, $tblBody);
    flushList($html, $inList, $listBuf);
    if ($inCode) {
        $html .= '<pre><code>' . htmlspecialchars(implode("\n", $codeBuf), ENT_QUOTES, 'UTF-8') . '</code></pre>' . "\n";
    }

    return [$html, $toc];
}

function flushTable(string &$html, bool &$inTable, string &$tblHead, string &$tblBody): void {
    if ($inTable) {
        $html .= '<table class="tsisip-table">' . "\n";
        $html .= '<thead>' . "\n" . str_replace('<td>', '<th>', str_replace('</td>', '</th>', $tblHead)) . "\n" . '</thead>' . "\n";
        $html .= '<tbody>' . "\n" . $tblBody . '</tbody>' . "\n";
        $html .= '</table>' . "\n";
        $inTable = false;
        $tblHead = '';
        $tblBody = '';
    }
}

function flushList(string &$html, bool &$inList, array &$listBuf): void {
    if ($inList && !empty($listBuf)) {
        $html .= "<ul>\n" . implode("\n", $listBuf) . "\n</ul>\n";
        $inList = false;
        $listBuf = [];
    }
}

function inlineMarkdown(string $text): string {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Bold + italic
    $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
    $text = preg_replace('/___(.+?)___/', '<strong><em>$1</em></strong>', $text);

    // Bold
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);

    // Italic
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);

    // Inline code
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);

    // Links
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $text);

    return $text;
}
?>
<div id="content" class="tsisip-wiki-page">
    <?php if (!$found): ?>
        <div class="tsisip-wiki-404">
            <h1><?php echo _('Page Not Found'); ?></h1>
            <p><?php echo _('The requested wiki page does not exist.'); ?></p>
            <a href="wiki.php" class="tsisip-btn tsisip-btn-primary">
                <?php echo _('Return to Wiki Home'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="tsisip-wiki-layout">
            <?php if (!empty($toc)): ?>
                <aside class="tsisip-wiki-toc" aria-label="<?php echo _('Table of Contents'); ?>">
                    <div class="tsisip-wiki-toc-header">
                        <h3><?php echo _('Contents'); ?></h3>
                        <input type="text"
                               class="tsisip-input tsisip-wiki-toc-search"
                               placeholder="<?php echo _('Search contents...'); ?>"
                               aria-label="<?php echo _('Filter table of contents'); ?>">
                    </div>
                    <ul class="tsisip-wiki-toc-list">
                        <?php foreach ($toc as $item): ?>
                            <li class="tsisip-wiki-toc-item tsisip-wiki-toc-level-<?php echo (int)$item['level']; ?>">
                                <a href="#<?php echo htmlspecialchars($item['anchor'], ENT_QUOTES, 'UTF-8'); ?>"
                                   class="tsisip-wiki-toc-link">
                                    <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
            <?php endif; ?>
            <article class="tsisip-wiki-content">
                <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                <?php echo $content; ?>
            </article>
        </div>
    <?php endif; ?>
</div>
<script src="tsisip/js/tsisip-wiki.js"></script>
<?php require_once __DIR__ . '/common/footer.php'; ?>
