<?php

declare(strict_types=1);

namespace MusicResort\Logger;

/**
 * Appends timestamped log lines to a file on disk.
 *
 * Line format:
 *   [2026-05-28 14:05:33] [INFO] Message {"key":"value"}
 *
 * The log directory is created automatically on first write.
 * Uses LOCK_EX to prevent interleaving when workers write concurrently.
 *
 * DI: receive $logPath via constructor (ADR-0002). Instantiate only in bin/console.
 */
final class FileLoggerService implements LoggerInterface
{
    public function __construct(
        private readonly string $logPath,
    ) {}

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->write('DEBUG', $message, $context);
    }

    /**
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     */
    private function write(string $level, string $message, array $context): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context !== []
            ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            : '';

        $line = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, recursive: true);
        }

        file_put_contents($this->logPath, $line, FILE_APPEND|LOCK_EX);
    }
}
