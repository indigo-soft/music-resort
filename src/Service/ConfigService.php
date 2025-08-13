<?php

declare(strict_types=1);

namespace Root\MusicLocal\Service;

use Root\MusicLocal\Exception\ConfigException;

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
            $appCfgRaw = require_once $appConfigFile;

            if (is_array($appCfgRaw)) {
                self::$config['app'] = self::resolveAppConfig($appCfgRaw);
            }
        }

        self::$initialized = true;
    }

    /**
     * Get config value by dot notation, e.g. get('app.debug', false)
     * @param string $key
     * @return int|string|bool
     */
    public static function get(string $key): int|string|bool
    {
        [$configKey, $paramKey] = explode('.', $key);
        $config = self::$config;

        if (array_key_exists($configKey, $config) === false) {
            throw new ConfigException('Config section: ' . $configKey . ' does not exist.');
        }

        if (array_key_exists($key, $config[$configKey])) {
            throw new ConfigException('Config parametr: ' . $paramKey . ' does not exist.');
        }

        return $config[$configKey][$paramKey];
    }

    /**
     * Read env value (from loaded .env or existing environment), with optional default.
     * @param string $key
     * @return string|bool|int
     */
    private static function env(string $key): string|bool|int
    {
        if (array_key_exists($key, self::$env)) {
            return self::$env[$key];
        }

        $val = match (true) {
            filter_has_var(INPUT_ENV, $key) => filter_input(INPUT_ENV, $key, FILTER_SANITIZE_SPECIAL_CHARS),
            filter_has_var(INPUT_SERVER, $key) => filter_input(INPUT_SERVER, $key, FILTER_SANITIZE_SPECIAL_CHARS),
            default => getenv($key)
        };

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
     * @param string $type
     * @param string|null $raw
     * @param string|int|bool $default
     * @return string|int|bool
     */
    private static function castValue(string $type, ?string $raw, string|int|bool $default): string|int|bool
    {
        $type = strtolower($type);
        return match ($type) {
            'bool', 'boolean' => self::castBool($raw, (bool)$default),
            'int', 'integer' => self::castInt($raw, is_int($default) ? $default : (int)$default),
            default => self::castString($raw, is_string($default) ? $default : (string)$default),
        };
    }

    /**
     * @param string|int|null $raw
     * @param bool $default
     * @return bool
     * @noinspection PhpUnused
     */
    private static function castBool(string|int|null $raw, bool $default): bool
    {
        if ($raw === null) {
            return $default;
        }

        $lower = strtolower(trim($raw));
        return match (true) {
            in_array($lower, [1, '1', 'true', 'yes', 'on'], true) => true,
            in_array($lower, [0, '0', 'false', 'no', 'off'], true) => false,
            default => $default,
        };
    }

    /**
     * @param string|int|null $raw
     * @param int $default
     * @return int
     * @noinspection PhpUnused
     */
    private static function castInt(string|int|null $raw, int $default): int
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
     * @param string|int|null $raw
     * @param string $default
     * @return string
     * @noinspection PhpUnused
     */
    private static function castString(string|int|null $raw, string $default): string
    {
        if ($raw === null) {
            return $default;
        }
        return (string)$raw;
    }
}
