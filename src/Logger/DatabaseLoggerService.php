<?php

declare(strict_types=1);

namespace MusicResort\Logger;

use MusicResort\Database\Repository\ProcessingLogRepository;
use MusicResort\Enum\LogLevel;

/**
 * SQLite-backed logger — replaces FileLoggerService as the single log sink.
 *
 * Writes structured rows to the processing_log table via ProcessingLogRepository.
 * Each instance is bound to a specific command name and run_id (UUIDv4), both
 * provided at construction time in bin/console.
 *
 * The run_id groups all entries from one command invocation, enabling per-run
 * queries: SELECT * FROM processing_log WHERE run_id = '...'.
 *
 * DI: receive ProcessingLogRepository, $command, $runId via constructor (ADR-0002).
 * Instantiate only in bin/console. Generate run_id there with generateRunId().
 */
final class DatabaseLoggerService implements LoggerInterface
{
    public function __construct(
        private readonly ProcessingLogRepository $repository,
        private readonly string $command,
        private readonly string $runId,
    ) {}

    /**
     * Generate a UUIDv4 run identifier.
     * Call once per command invocation in bin/console and pass to the constructor.
     */
    public static function generateRunId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f)|0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f)|0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->write(LogLevel::Info, $message, $context);
    }

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->write(LogLevel::Warning, $message, $context);
    }

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->write(LogLevel::Error, $message, $context);
    }

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->write(LogLevel::Debug, $message, $context);
    }

    /**
     * @param LogLevel $level
     * @param string $message
     * @param array<string, mixed> $context
     */
    private function write(LogLevel $level, string $message, array $context): void
    {
        $this->repository->insert(
            command: $this->command,
            level: $level,
            message: $message,
            runId: $this->runId,
            context: $context,
        );
    }
}
