<?php

declare(strict_types=1);

use Root\MusicLocal\Service\LocalizationService;

if (!function_exists('__')) {
    /**
     * Translate the given message.
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        return LocalizationService::get($key, $replace, $locale);
    }
}
