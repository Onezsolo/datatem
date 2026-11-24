<?php

namespace Tests\Unit;

use Tests\TestCase;

class ThemePresetsTest extends TestCase
{
    public function test_five_system_design_presets_exist(): void
    {
        $this->assertCount(5, config('theme.presets'));
    }

    public function test_every_preset_meets_wcag_aa_text_contrast(): void
    {
        $pairs = [
            ['brand-navy', 'surface'],
            ['brand-navy', 'surface-muted'],
            ['surface', 'brand-navy'],
            ['brand-navy', 'brand-yellow'],
            ['brand-navy', 'brand-yellow-strong'],
            ['surface', 'brand-red-strong'],
            ['surface', 'brand-red-strong-hover'],
            ['muted', 'surface'],
            ['nav-muted', 'brand-navy'],
        ];

        foreach (config('theme.presets') as $key => $preset) {
            $colors = $preset['colors'];

            foreach ($pairs as [$foreground, $background]) {
                $ratio = $this->contrastRatio($colors[$foreground], $colors[$background]);

                $this->assertGreaterThanOrEqual(
                    4.5,
                    $ratio,
                    "Preset {$key}: {$foreground} on {$background} is {$ratio}:1."
                );
            }
        }
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $lighter = max($this->luminance($foreground), $this->luminance($background));
        $darker = min($this->luminance($foreground), $this->luminance($background));

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];

        $linear = array_map(
            static fn (float $channel): float => $channel <= 0.03928
                ? $channel / 12.92
                : (($channel + 0.055) / 1.055) ** 2.4,
            $channels
        );

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }
}
