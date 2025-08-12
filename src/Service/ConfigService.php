<?php

declare(strict_types=1);

namespace Root\MusicLocal\Service;

final class ConfigService
{
    private static array $config = [];
    private static array $env = [];
    private static bool $initialized = false;

    /**
     * @param string|null $projectRoot
     * @return void
     */
    public static function init(?string $projectRoot = null): void
    {
        if (self::$initialized) {
            return;
        }

        $projectRoot = $projectRoot ?? dirname(__DIR__, 2);

        // 1) Load .env
        self::loadEnv($projectRoot . DIRECTORY_SEPARATOR . '.env');

        // 2) Load config files
        $configDir = $projectRoot . DIRECTORY_SEPARATOR . 'config';
        $appConfigFile = $configDir . DIRECTORY_SEPARATOR . 'app.php';

        if (is_file($appConfigFile)) {
            /** @var array $appCfgRaw */
            $appCfgRaw = include $appConfigFile;

            if (is_array($appCfgRaw)) {
                self::$config['app'] = self::resolveAppConfig($appCfgRaw);
            }
        }

        self::$initialized = true;
    }

    /**
     * Get config value by dot notation, e.g. get('app.debug', false)
     * @param string $key
     * @param int|string|bool|null $default
     * @return int|string|bool|null
     */
    public static function get(string $key, int|string|bool|null $default = null): int|string|bool|null
    {
        $segments = explode('.', $key);
        $current = self::$config;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * Read env value (from loaded .env or existing environment), with optional default.
     */
    public static function env(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$env)) {
            return self::$env[$key];
        }
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }
        return (string)$val;
    }

    /**
     * @param string $filePath
     * @return void
     */
    private static function loadEnv(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return;
        }
        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            // allow comments after values using # but not inside quotes
            $quote = null;
            $buf = '';
            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $ch = $line[$i];
                if ($ch === '"' || $ch === "'") {
                    if ($quote === null) {
                        $quote = $ch;
                    } elseif ($quote === $ch) {
                        $quote = null;
                    }
                    $buf .= $ch;
                    continue;
                }
                if ($ch === '#' && $quote === null) {
                    break; // comment start
                }
                $buf .= $ch;
            }
            $line = trim($buf);
            if ($line === '') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $valueRaw = trim(substr($line, $pos + 1));
            // Remove optional quotes
            if ((str_starts_with($valueRaw, '"') && str_ends_with($valueRaw, '"')) || (str_starts_with($valueRaw, "'") && str_ends_with($valueRaw, "'"))) {
                $valueRaw = substr($valueRaw, 1, -1);
            }
            // Expand simple escapes for new line / carriage returns
            $valueRaw = str_replace(["\\n", "\\r", "\\t"], ["\n", "\r", "\t"], $valueRaw);
            self::$env[$key] = $valueRaw;
            // Also populate superglobals for broader compatibility
            $_ENV[$key] = $valueRaw;
            $_SERVER[$key] = $valueRaw;
            putenv($key . '=' . $valueRaw);
        }
    }

    /**
     * Resolve app config schema into final values.
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private static function resolveAppConfig(array $raw): array
    {
        $resolved = [];
        foreach ($raw as $key => $definition) {
            if (is_array($definition)) {
                $envName = $definition['name'] ?? $definition['env'] ?? null;
                $type = strtolower((string)($definition['type'] ?? 'string'));
                $default = $definition['default'] ?? null;

                if (!is_string($envName) || $envName === '') {
                    // No env name provided, just keep the default as-is
                    $resolved[$key] = self::castValue($type, null, $default);
                    continue;
                }
                $rawValue = self::env($envName);
                $resolved[$key] = self::castValue($type, $rawValue, $default);
            } else {
                // Already a scalar or other value
                $resolved[$key] = $definition;
            }
        }
        return $resolved;
    }

    /**
     * Cast raw env string to requested type with default fallback.
     */
    private static function castValue(string $type, ?string $raw, mixed $default): mixed
    {
        $type = strtolower($type);
        return match ($type) {
            'bool', 'boolean' => self::castBool($raw, (bool)$default),
            'int', 'integer' => self::castInt($raw, is_int($default) ? $default : (int)$default),
            default => self::castString($raw, is_string($default) ? $default : (string)$default),
        };
    }

    /**
     * @param string|null $raw
     * @param bool $default
     * @return bool
     */
    private static function castBool(?string $raw, bool $default): bool
    {
        if ($raw === null) {
            return $default;
        }
        $lower = strtolower(trim($raw));
        if (in_array($lower, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($lower, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
        return $default;
    }

    /**
     * @param string|null $raw
     * @param int $default
     * @return int
     */
    private static function castInt(?string $raw, int $default): int
    {
        if ($raw === null || $raw === '') {
            return $default;
        }
        if (is_numeric($raw)) {
            return (int)$raw;
        }
        return $default;
    }

    /**
     * @param string|null $raw
     * @param string $default
     * @return string
     */
    private static function castString(?string $raw, string $default): string
    {
        if ($raw === null) {
            return $default;
        }
        return (string)$raw;
    }

    /** Small helpers for typed config values */
    public static function bool(string $key, bool $default = false): bool
    {
        $val = self::get($key);
        if (is_bool($val)) {
            return $val;
        }
        if (is_string($val)) {
            $lower = strtolower($val);
            return in_array($lower, ['1', 'true', 'yes', 'on'], true) || ((!in_array($lower, ['0', 'false', 'no', 'off'], true) && $default));
        }
        if (is_numeric($val)) {
            return ((int)$val) !== 0;
        }
        return $default;
    }
}
