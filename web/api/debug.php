<?php
echo json_encode([
    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? null,
    'php_self' => $_SERVER['PHP_SELF'] ?? null,
    'query_string' => $_SERVER['QUERY_STRING'] ?? null,
], JSON_PRETTY_PRINT);
