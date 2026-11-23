<?php

use App\Services\SettingsService;

if (! function_exists('settings')) {
    /**
     * Get a setting value (from DB with fallback to config).
     */
    function settings(string $key, ?string $default = null): ?string
    {
        return SettingsService::get($key, $default);
    }
}
