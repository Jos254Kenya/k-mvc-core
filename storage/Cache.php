<?php

namespace sigawa\mvccore\storage;

class Cache
{
    protected static string $cacheDir = __DIR__ . '/cache/';
    protected static bool $useJson = true;
    protected static bool $useCompression = false;

    // In-memory cache (only lasts during this PHP request)
    protected static array $memoryCache = [];

    // Map of tags to cache keys
    protected static array $tagMap = [];

    public static function configure(array $options): void
    {
        if (isset($options['cacheDir'])) {
            self::$cacheDir = rtrim($options['cacheDir'], '/') . '/';
        }
        if (isset($options['useJson'])) {
            self::$useJson = (bool)$options['useJson'];
        }
        if (isset($options['useCompression'])) {
            self::$useCompression = (bool)$options['useCompression'];
        }
    }
    public static function remember(string $key, int $ttl, callable $callback, string $namespace = '', array $tags = [])
    {
        if (self::has($key, $namespace)) {
            return self::get($key, $namespace);
        }

        $value = $callback();
        self::set($key, $value, $ttl, $namespace, $tags);
        return $value;
    }

    protected static function getCacheFile(string $key, string $namespace = ''): string
    {
        $nsPath = $namespace ? md5($namespace) . '_' : '';
        return self::$cacheDir . $nsPath . md5($key) . '.cache';
    }

    public static function set(string $key, $value, int $ttl = 3600, string $namespace = '', array $tags = []): bool
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
        }

        $data = [
            'created_at' => time(),
            'expires_at' => time() + $ttl,
            'value' => self::$useJson ? json_encode($value) : serialize($value),
            'tags' => $tags,
        ];

        $encoded = self::$useJson ? json_encode($data) : serialize($data);
        if (self::$useCompression) {
            $encoded = gzcompress($encoded);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'cache_');
        $result = file_put_contents($tempFile, $encoded);
        if ($result === false) return false;

        $path = self::getCacheFile($key, $namespace);
        $success = rename($tempFile, $path);

        // Save to in-memory cache
        self::$memoryCache[$namespace][$key] = $value;

        // Save tag mapping
        foreach ($tags as $tag) {
            self::$tagMap[$tag][] = [$key, $namespace];
        }

        return $success;
    }

    public static function get(string $key, string $namespace = '')
    {
        if (isset(self::$memoryCache[$namespace][$key])) {
            return self::$memoryCache[$namespace][$key];
        }

        $file = self::getCacheFile($key, $namespace);
        if (!file_exists($file)) return null;

        $contents = file_get_contents($file);
        if (self::$useCompression) {
            $contents = gzuncompress($contents);
        }

        $data = self::$useJson ? json_decode($contents, true) : unserialize($contents);
        if (!is_array($data) || !isset($data['expires_at'])) {
            self::delete($key, $namespace);
            return null;
        }

        if ($data['expires_at'] < time()) {
            self::delete($key, $namespace);
            return null;
        }

        $value = self::$useJson ? json_decode($data['value'], true) : unserialize($data['value']);
        self::$memoryCache[$namespace][$key] = $value;

        return $value;
    }

    public static function has(string $key, string $namespace = ''): bool
    {
        if (isset(self::$memoryCache[$namespace][$key])) {
            return true;
        }

        $file = self::getCacheFile($key, $namespace);
        if (!file_exists($file)) return false;

        $contents = file_get_contents($file);
        if (self::$useCompression) {
            $contents = gzuncompress($contents);
        }

        $data = self::$useJson ? json_decode($contents, true) : unserialize($contents);
        if (!is_array($data) || !isset($data['expires_at']) || $data['expires_at'] < time()) {
            self::delete($key, $namespace);
            return false;
        }

        return true;
    }

    public static function delete(string $key, string $namespace = ''): bool
    {
        unset(self::$memoryCache[$namespace][$key]);
        $file = self::getCacheFile($key, $namespace);
        return file_exists($file) ? unlink($file) : false;
    }

    public static function clear(string $namespace = ''): void
    {
        unset(self::$memoryCache[$namespace]);

        if (!is_dir(self::$cacheDir)) return;

        $prefix = $namespace ? md5($namespace) . '_' : '';
        foreach (glob(self::$cacheDir . $prefix . '*.cache') as $file) {
            unlink($file);
        }
    }

    public static function clearByTag(string $tag): void
    {
        if (!isset(self::$tagMap[$tag])) return;

        foreach (self::$tagMap[$tag] as [$key, $namespace]) {
            self::delete($key, $namespace);
        }

        unset(self::$tagMap[$tag]);
    }

    public static function cleanup(): void
    {
        if (!is_dir(self::$cacheDir)) return;

        foreach (glob(self::$cacheDir . '*.cache') as $file) {
            $contents = file_get_contents($file);
            if (self::$useCompression) {
                $contents = gzuncompress($contents);
            }

            $data = self::$useJson ? json_decode($contents, true) : unserialize($contents);
            if (!is_array($data) || (isset($data['expires_at']) && $data['expires_at'] < time())) {
                unlink($file);
            }
        }
    }

    public static function info(string $key, string $namespace = ''): ?array
    {
        $file = self::getCacheFile($key, $namespace);
        if (!file_exists($file)) return null;

        $contents = file_get_contents($file);
        if (self::$useCompression) {
            $contents = gzuncompress($contents);
        }

        $data = self::$useJson ? json_decode($contents, true) : unserialize($contents);
        if (!is_array($data) || !isset($data['expires_at'])) {
            return null;
        }

        return [
            'created_at' => $data['created_at'] ?? null,
            'expires_at' => $data['expires_at'],
            'remaining_seconds' => $data['expires_at'] - time(),
            'tags' => $data['tags'] ?? [],
        ];
    }
}
