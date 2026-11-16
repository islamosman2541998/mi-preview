<?php

namespace App\Support;

use App\Models\Site;

/**
 * Turns a site's base palette (primary, secondary, accent, text, bg) into the
 * full set of CSS custom properties templates use: shades, soft tints, and
 * readable "on-*" text colours chosen by WCAG contrast.
 */
class Palette
{
    public const DEFAULTS = [
        'primary' => '#1F3A5F',
        'secondary' => '#F2A900',
        'accent' => '#D9480F',
        'text' => '#1B1F24',
        'bg' => '#F5F5F2',
    ];

    /** @return array<string, string> base colours, validated, with fallbacks */
    public static function base(Site $site): array
    {
        $colors = [];

        foreach (self::DEFAULTS as $key => $default) {
            $value = $site->palette[$key] ?? $site->industry?->default_palette[$key] ?? $default;
            $colors[$key] = self::normalize($value) ?? $default;
        }

        return $colors;
    }

    /** @return array<string, string> "--c-*" => "#rrggbb" */
    public static function cssVariables(Site $site): array
    {
        $c = self::base($site);
        $white = '#FFFFFF';
        $black = '#000000';

        $vars = [
            'primary' => $c['primary'],
            'primary-dark' => self::mix($c['primary'], $black, 0.35),
            'primary-deep' => self::mix($c['primary'], $black, 0.6),
            'primary-soft' => self::mix($c['primary'], $c['bg'], 0.9),
            'secondary' => $c['secondary'],
            'secondary-dark' => self::mix($c['secondary'], $black, 0.18),
            'accent' => $c['accent'],
            'text' => $c['text'],
            'muted' => self::mix($c['text'], $c['bg'], 0.38),
            'line' => self::mix($c['text'], $c['bg'], 0.86),
            'bg' => $c['bg'],
            'surface' => self::mix($c['primary'], self::mix($c['bg'], $white, 0.5), 0.95),
            'paper' => $white,
        ];

        $vars['on-primary'] = self::readableOn($vars['primary']);
        $vars['on-primary-deep'] = self::readableOn($vars['primary-deep']);
        $vars['on-secondary'] = self::readableOn($vars['secondary'], $c['text']);
        $vars['on-accent'] = self::readableOn($vars['accent']);

        return collect($vars)->mapWithKeys(fn ($v, $k) => ["--c-{$k}" => $v])->all();
    }

    public static function inlineCss(Site $site): string
    {
        return collect(self::cssVariables($site))
            ->map(fn ($v, $k) => "{$k}:{$v}")
            ->implode(';');
    }

    // ---------------------------------------------------------------- colour math

    public static function normalize(mixed $hex): ?string
    {
        if (! is_string($hex)) {
            return null;
        }

        $hex = ltrim(trim($hex), '#');

        if (preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
            $hex = preg_replace('/(.)/', '$1$1', $hex);
        }

        return preg_match('/^[0-9a-fA-F]{6}$/', $hex) ? '#'.strtoupper($hex) : null;
    }

    /** @return array{0:int,1:int,2:int} */
    public static function rgb(string $hex): array
    {
        $hex = ltrim(self::normalize($hex) ?? '#000000', '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /** Mix $a toward $b by $weight (0 = $a, 1 = $b). */
    public static function mix(string $a, string $b, float $weight): string
    {
        [$r1, $g1, $b1] = self::rgb($a);
        [$r2, $g2, $b2] = self::rgb($b);

        $ch = fn ($x, $y) => (int) round($x + ($y - $x) * $weight);

        return sprintf('#%02X%02X%02X', $ch($r1, $r2), $ch($g1, $g2), $ch($b1, $b2));
    }

    public static function luminance(string $hex): float
    {
        $lin = function (int $v): float {
            $c = $v / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        [$r, $g, $b] = self::rgb($hex);

        return 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
    }

    public static function contrast(string $a, string $b): float
    {
        $l1 = self::luminance($a);
        $l2 = self::luminance($b);

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    /** White or a dark ink — whichever reads better on $background. */
    public static function readableOn(string $background, string $dark = '#14171A'): string
    {
        return self::contrast($background, '#FFFFFF') >= self::contrast($background, $dark) ? '#FFFFFF' : $dark;
    }

    /** Best contrast any text colour (white or dark ink) reaches on $background. */
    public static function bestTextContrast(string $background, string $dark = '#14171A'): float
    {
        return self::contrast($background, self::readableOn($background, $dark));
    }

    /**
     * Darken $hex in small steps until white or dark text on it meets $ratio
     * (WCAG AA = 4.5). Brand colours that already pass are returned untouched.
     */
    public static function ensureReadable(string $hex, float $ratio = 4.5, string $dark = '#14171A'): string
    {
        $hex = self::normalize($hex) ?? '#000000';

        for ($i = 0; $i < 20 && self::bestTextContrast($hex, $dark) < $ratio; $i++) {
            $hex = self::mix($hex, '#000000', 0.06);
        }

        return $hex;
    }

    /**
     * Tailwind-like 50…900 scale around a base colour (500 = base).
     *
     * @return array<int, string>
     */
    public static function shades(string $hex): array
    {
        $hex = self::normalize($hex) ?? '#000000';
        $steps = [50 => 0.92, 100 => 0.84, 200 => 0.68, 300 => 0.5, 400 => 0.26, 500 => 0, 600 => -0.15, 700 => -0.32, 800 => -0.48, 900 => -0.62];

        return array_map(
            fn (float $w) => $w >= 0 ? self::mix($hex, '#FFFFFF', $w) : self::mix($hex, '#000000', -$w),
            $steps,
        );
    }

    /** Stored palette: the five base colours plus generated shades (for export / templates). */
    public static function withShades(array $palette): array
    {
        $base = [];
        foreach (array_keys(self::DEFAULTS) as $key) {
            if ($color = self::normalize($palette[$key] ?? null)) {
                $base[$key] = $color;
            }
        }

        if ($base === []) {
            return [];
        }

        $base['shades'] = collect(['primary', 'secondary', 'accent'])
            ->filter(fn ($k) => isset($base[$k]))
            ->mapWithKeys(fn ($k) => [$k => self::shades($base[$k])])
            ->all();

        return $base;
    }

    /** @return array{0:float,1:float,2:float} hue 0–360, saturation 0–1, lightness 0–1 */
    public static function hsl(string $hex): array
    {
        [$r, $g, $b] = array_map(fn ($v) => $v / 255, self::rgb($hex));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d == 0) {
            return [0.0, 0.0, $l];
        }

        $s = $d / (1 - abs(2 * $l - 1));
        $h = match ($max) {
            $r => fmod(($g - $b) / $d + 6, 6),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };

        return [$h * 60, $s, $l];
    }
}
