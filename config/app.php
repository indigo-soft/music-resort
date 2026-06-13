<?php

declare(strict_types=1);

return [
    // When true, all commands should behave as if --dry-run is provided
    'debug' => [
        'name'    => 'DEBUG',
        'type'    => 'bool',
        'default' => false,
    ],
    // Default language code (e.g., 'en', 'uk')
    'default_lang' => [
        'name'    => 'DEFAULT_LANG',
        'type'    => 'string',
        'default' => 'en',
    ],
    // MariaDB connection parameters
    'db_host' => [
        'name'    => 'DB_HOST',
        'type'    => 'string',
        'default' => '127.0.0.1',
    ],
    'db_port' => [
        'name'    => 'DB_PORT',
        'type'    => 'string',
        'default' => '3306',
    ],
    'db_name' => [
        'name'    => 'DB_NAME',
        'type'    => 'string',
        'default' => 'music_resort',
    ],
    'db_user' => [
        'name'    => 'DB_USER',
        'type'    => 'string',
        'default' => 'root',
    ],
    'db_password' => [
        'name'    => 'DB_PASSWORD',
        'type'    => 'string',
        'default' => '',
    ],
    // Last.fm API key (https://www.last.fm/api/account/create)
    'lastfm_api_key' => [
        'name'    => 'LASTFM_API_KEY',
        'type'    => 'string',
        'default' => '',
    ],
    // Last.fm API root endpoint
    'lastfm_api_url' => [
        'name'    => 'LASTFM_API_URL',
        'type'    => 'string',
        'default' => 'https://ws.audioscrobbler.com/2.0/',
    ],
    // Days before cached Last.fm artist tags are considered stale
    'lastfm_cache_ttl_days' => [
        'name'    => 'LASTFM_CACHE_TTL_DAYS',
        'type'    => 'int',
        'default' => 30,
    ],
    // Path to the application log file.
    'log_path' => [
        'name'    => 'LOG_PATH',
        'type'    => 'string',
        'default' => './storage/logs/app.log',
    ],
    // Comma-separated list of tables preserved by migrate:refresh.
    'migration_preserve_tables' => [
        'name'    => 'MIGRATION_PRESERVE_TABLES',
        'type'    => 'string',
        'default' => 'migrations,processing_log',
    ],
];
