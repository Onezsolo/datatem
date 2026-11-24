<?php

namespace Tests\Unit;

use Tests\TestCase;

class DesignTokensTest extends TestCase
{
    /**
     * @var array<string, string>
     */
    private array $colors = [];

    private string $css = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = (string) file_get_contents(resource_path('css/tokens.css'));

        preg_match_all(
            '/--color-([a-z0-9-]+):\s*(#[0-9a-fA-F]{6});/',
            $this->css,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $this->colors[$match[1]] = strtolower($match[2]);
        }
    }

    public function test_required_design_tokens_are_exported(): void
    {
        $required = [
            'brand-navy',
            'brand-yellow',
            'brand-yellow-strong',
            'brand-red',
            'brand-red-strong',
            'brand-red-strong-hover',
            'surface',
            'surface-muted',
            'ink',
            'muted',
            'line',
            'nav-muted',
        ];

        foreach ($required as $token) {
            $this->assertArrayHasKey($token, $this->colors, "Missing design token [{$token}].");
        }

        $this->assertSame('#001534', $this->colors['brand-navy']);
        $this->assertSame('#ffcc00', $this->colors['brand-yellow']);
        $this->assertSame('#e63946', $this->colors['brand-red']);
        $this->assertSame('#b0202d', $this->colors['brand-red-strong']);

        $this->assertStringContainsString('--breakpoint-sm: 40rem;', $this->css);
        $this->assertStringContainsString('--breakpoint-md: 48rem;', $this->css);
        $this->assertStringContainsString('--breakpoint-lg: 64rem;', $this->css);
        $this->assertStringContainsString('--radius-control: 0.375rem;', $this->css);
        $this->assertStringContainsString('--radius-card: 0.75rem;', $this->css);
    }

    public function test_text_and_control_pairs_meet_wcag_aa(): void
    {
        $pairs = [
            ['brand-navy', 'surface'],
            ['brand-navy', 'brand-yellow'],
            ['surface', 'brand-navy'],
            ['brand-navy', 'surface-muted'],
            ['muted', 'surface'],
            ['muted', 'surface-muted'],
            ['nav-muted', 'brand-navy'],
            ['surface', 'brand-red-strong'],
            ['brand-navy', 'brand-yellow-strong'],
            ['surface', 'brand-red-strong-hover'],
        ];

        foreach ($pairs as [$foreground, $background]) {
            $ratio = $this->contrastRatio($this->colors[$foreground], $this->colors[$background]);

            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                "Contrast for {$foreground} on {$background} is {$ratio}:1; WCAG AA requires 4.5:1."
            );
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
