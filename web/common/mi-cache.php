<?php
/**
 * TSiSIP Control Panel — MI Response Cache
 * Simple in-memory cache with TTL for MI HTTP calls.
 */

class MICache {
    private static array $cache = [];
    private static int $defaultTtl = 5;

    public static function get(string $key) {
        $now = time();
        if (isset(self::$cache[$key])) {
            if (self::$cache[$key]['expires'] > $now) {
                return self::$cache[$key]['data'];
            }
            unset(self::$cache[$key]);
        }
        return null;
    }

    public static function set(string $key, $data, int $ttl = null): void {
        self::$cache[$key] = [
            'data' => $data,
            'expires' => time() + ($ttl ?? self::$defaultTtl),
        ];
    }

    public static function invalidate(string $pattern = null): void {
        if ($pattern === null) {
            self::$cache = [];
            return;
        }
        foreach (self::$cache as $key => $entry) {
            if (fnmatch($pattern, $key)) {
                unset(self::$cache[$key]);
            }
        }
    }

    public static function stats(): array {
        return [
            'entries' => count(self::$cache),
            'memory_kb' => round(strlen(serialize(self::$cache)) / 1024, 2),
        ];
    }
}
