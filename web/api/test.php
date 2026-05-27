<?php
header('Content-Type: text/plain');
echo "DIR: " . __DIR__ . "\n";
echo "FILE: " . __DIR__ . "/v1/status.php\n";
echo "EXISTS: " . (file_exists(__DIR__ . "/v1/status.php") ? "YES" : "NO") . "\n";
