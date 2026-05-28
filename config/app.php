<?php

declare(strict_types=1);

return [
    // When true, all commands should behave as if --dry-run is provided
    'debug' => [
        'name' => 'DEBUG',
        'type' => 'bool',
        'default' => false,
    ],
    // Default language code (e.g., 'en', 'uk')
    'default_lang' => [
        'name' => 'DEFAULT_LANG',
        'type' => 'string',
        'default' => 'en',
    ],
    // Path to the SQLite database file.
    // Relative paths are resolved from the project root in bin/console.
    'db_path' => [
        'name' => 'DB_PATH',
        'type' => 'string',
        'default' => './db/music.sqlite',
    ],
    // Path to the application log file.
    // Relative paths are resolved from the project root in bin/console.
    'log_path' => [
        'name' => 'LOG_PATH',
        'type' => 'string',
        'default' => './storage/logs/app.log',
    ],
];
