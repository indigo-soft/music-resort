<?php

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
];
