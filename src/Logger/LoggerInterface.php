<?php

declare(strict_types=1);

namespace MusicResort\Logger;

/**
 * Minimal logging contract for MusicResort services.
 *
 * Intentionally small: only the four levels actually used in this project.
 * Implementations receive the log path via constructor (ADR-0002).
 */
interface LoggerInterface
{
    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void;

    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void;
}
