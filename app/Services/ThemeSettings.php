<?php

declare(strict_types=1);

namespace App\Services;

final class ThemeSettings
{
    private const SETTINGS_KEY = 'theme_preset';

    /**
     * @return array<string, array{label: string, colors: array<string, string>}>
     */
    public static function presets(): array
    {
        return (array) config('theme.presets', []);
    }

    public static function defaultKey(): string
    {
        $presets = self::presets();

        return (string) (array_key_first($presets) ?? 'navy-gold');
    }

    public static function currentKey(): string
    {
        $key = (string) SettingsService::get(self::SETTINGS_KEY, '');

        return array_key_exists($key, self::presets()) ? $key : self::defaultKey();
    }

    /**
     * @return array{label: string, colors: array<string, string>}
     */
    public static function current(): array
    {
        return self::presets()[self::currentKey()] ?? ['label' => 'Default', 'colors' => []];
    }

    public static function set(string $key): void
    {
        if (! array_key_exists($key, self::presets())) {
            $key = self::defaultKey();
        }

        SettingsService::setMany([self::SETTINGS_KEY => $key]);
    }

    /**
     * @return array<string, string>
     */
    public static function cssVariables(): array
    {
        $variables = [];

        foreach (self::current()['colors'] as $name => $value) {
            $variables['--color-'.$name] = $value;
        }

        return $variables;
    }
}
