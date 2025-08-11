<?php

declare(strict_types=1);

namespace Root\MusicLocal\Service;

final class LocalizationService
{
    private static string $locale = 'uk';
    private static ?string $basePath = null;
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Laravel-like translator
     * Example: __('console.success.resorted', ['processed' => 10])
     */
    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::$locale;
        [$file, $innerKey] = self::splitKey($key);

        $lines = self::loadFile($file, $locale);
        $value = self::dataGet($lines, $innerKey, $key);

        if (!is_string($value)) {
            // Fallback to key if non-string
            $value = $key;
        }

        return self::makeReplacements($value, $replace);
    }

    private static function splitKey(string $key): array
    {
        $pos = strpos($key, '.');
        if ($pos === false) {
            // default file is 'console'
            return ['console', $key];
        }
        return [substr($key, 0, $pos), substr($key, $pos + 1)];
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadFile(string $file, string $locale): array
    {
        // default base is project_root/lang
        $base = self::$basePath ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'lang';
        $cacheKey = $locale . ':' . $file;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $path = $base . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $file . '.php';
        if (is_file($path)) {
            /** @var array $data */
            $data = include $path;
            if (is_array($data)) {
                return self::$cache[$cacheKey] = $data;
            }
        }

        return self::$cache[$cacheKey] = [];
    }

    /**
     * Dot-notation array get
     * @param array<string, mixed> $array
     */
    private static function dataGet(array $array, string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $array;
        }
        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        return $array;
    }

    private static function makeReplacements(string $line, array $replace): string
    {
        if ($replace === []) {
            return $line;
        }
        $search = [];
        $values = [];
        foreach ($replace as $key => $value) {
            $search[] = ':' . $key;
            $values[] = (string)$value;
        }
        return str_replace($search, $values, $line);
    }
}
