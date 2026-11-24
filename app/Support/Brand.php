<?php

namespace App\Support;

use App\Services\SettingsService;

final class Brand
{
    public static function name(): string
    {
        $name = trim((string) SettingsService::get('site_name', ''));

        return $name !== '' ? $name : (string) config('app.name', 'Datatem');
    }

    public static function logoUrl(): string
    {
        return SettingsService::getLogoUrl();
    }

    public static function faviconUrl(): string
    {
        return SettingsService::getFaviconUrl();
    }
}
