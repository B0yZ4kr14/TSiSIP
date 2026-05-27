<?php
/**
 * TSiSIP Control Panel — Simple Page Cache
 * File-based cache for expensive page renders.
 */

class PageCache {
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct(string $cacheDir = __DIR__ . '/../../cache', int $defaultTtl = 60) {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->defaultTtl = $defaultTtl;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0750, true);
        }
    }

    public function get(string $key): ?string {
        $file = $this->cacheDir . '/' . $this->sanitizeKey($key) . '.html';
        if (!file_exists($file)) return null;
        if (filemtime($file) + $this->defaultTtl < time()) {
            unlink($file);
            return null;
        }
        return file_get_contents($file);
    }

    public function set(string $key, string $content, int $ttl = null): void {
        $file = $this->cacheDir . '/' . $this->sanitizeKey($key) . '.html';
        file_put_contents($file, $content, LOCK_EX);
    }

    public function invalidate(string $pattern = null): void {
        foreach (glob($this->cacheDir . '/*.html') as $file) {
            if ($pattern === null || fnmatch($pattern, basename($file))) {
                unlink($file);
            }
        }
    }

    public function stats(): array {
        $files = glob($this->cacheDir . '/*.html');
        $size = 0;
        foreach ($files as $f) $size += filesize($f);
        return [
            'entries' => count($files),
            'size_kb' => round($size / 1024, 2),
        ];
    }

    private function sanitizeKey(string $key): string {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    }
}
