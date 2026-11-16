<?php

namespace App\Services;

use App\Support\Palette;
use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette as PixelPalette;
use RuntimeException;

/**
 * Builds a site palette from a logo file.
 *
 *  - the image is shrunk to ~120 px first (cheap on a shared host's CPU limits)
 *  - transparent pixels are ignored; whites, blacks and greys are "neutral"
 *  - primary   = the most representative brand colour that can carry white/dark text (AA),
 *                darkened just enough when it can't
 *  - secondary = the next clearly different brand colour (buttons), else the industry's
 *  - accent    = a third brand colour, else the industry's
 *  - text / bg = a near-black and an off-white tinted with the primary hue
 *  - a logo with no brand colour at all (black/white/grey) falls back to the industry palette
 */
class LogoPalette
{
    private const SAMPLE_WIDTH = 120;

    /**
     * @param  array<string,string>|null  $fallback  industry palette (or null for the global defaults)
     * @return array{palette: array<string,string>, monochrome: bool, colors: list<string>}
     */
    public function fromFile(string $path, ?array $fallback = null): array
    {
        $colors = $this->brandColors($path);
        $fallback = array_merge(Palette::DEFAULTS, array_filter((array) $fallback, fn ($v) => Palette::normalize($v)));

        if ($colors === []) {
            return ['palette' => array_intersect_key($fallback, Palette::DEFAULTS), 'monochrome' => true, 'colors' => []];
        }

        $primary = $this->pickPrimary($colors);
        $others = array_values(array_filter($colors, fn ($c) => $c !== $primary && $this->distinct($c, $primary)));

        // templates put white text on primary: a light brand colour (yellow, lime…) becomes the
        // button colour instead, and primary turns into a charcoal tinted with it
        if (Palette::contrast($primary, '#FFFFFF') < 3) {
            array_unshift($others, $primary);
            $primary = Palette::mix($primary, '#15181C', 0.8);
        }

        $primary = Palette::ensureReadable($primary);

        $palette = [
            'primary' => $primary,
            'secondary' => $others[0] ?? $fallback['secondary'],
            'accent' => $others[1] ?? $fallback['accent'],
            'text' => Palette::mix($primary, '#0B0D10', 0.86),
            'bg' => Palette::mix($primary, '#FAFAF8', 0.955),
        ];

        // a button colour must be able to carry its label too
        $palette['secondary'] = Palette::ensureReadable($palette['secondary']);

        return ['palette' => $palette, 'monochrome' => false, 'colors' => $colors];
    }

    /** @return list<string> brand (non-neutral) colours, most representative first */
    public function brandColors(string $path): array
    {
        $image = $this->load($path);
        $pixels = PixelPalette::fromGD($image); // transparent pixels are skipped
        imagedestroy($image);

        if (count($pixels) === 0) {
            return [];
        }

        $total = array_sum(iterator_to_array($pixels));
        $colors = [];

        // most-used colours, merged by the extractor into a few representative ones
        foreach ((new ColorExtractor($pixels))->extract(8) as $int) {
            $hex = Palette::normalize(Color::fromIntToHex($int));

            if ($hex && ! $this->isNeutral($hex) && ! in_array($hex, $colors, true)) {
                $colors[] = $hex;
            }
        }

        // drop specks: colours covering < 1.5% of the visible logo
        $share = $this->coverage($pixels, $total, $colors);

        return array_values(array_filter($colors, fn ($c) => ($share[$c] ?? 0) >= 0.015));
    }

    private function load(string $path)
    {
        $contents = @file_get_contents($path);
        $image = $contents !== false ? @imagecreatefromstring($contents) : false;

        if (! $image) {
            throw new RuntimeException('Unreadable image: '.basename($path));
        }

        if (imagesx($image) > self::SAMPLE_WIDTH) {
            $small = imagescale($image, self::SAMPLE_WIDTH, -1, IMG_BILINEAR_FIXED);
            imagedestroy($image);
            $image = $small;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    /** Share of visible pixels closest to each candidate colour. */
    private function coverage(PixelPalette $pixels, int $total, array $candidates): array
    {
        if ($candidates === [] || $total === 0) {
            return [];
        }

        $share = array_fill_keys($candidates, 0);

        foreach ($pixels as $int => $count) {
            $hex = Color::fromIntToHex($int);
            if ($this->isNeutral($hex)) {
                continue;
            }
            $nearest = null;
            $best = PHP_FLOAT_MAX;
            foreach ($candidates as $c) {
                $d = $this->distance($hex, $c);
                if ($d < $best) {
                    [$best, $nearest] = [$d, $c];
                }
            }
            if ($best < 90) {
                $share[$nearest] += $count;
            }
        }

        return array_map(fn ($n) => $n / $total, $share);
    }

    /** Prefer a colour that can already carry text; otherwise the dominant one (darkened later). */
    private function pickPrimary(array $colors): string
    {
        foreach (array_slice($colors, 0, 3) as $c) {
            if (Palette::contrast($c, '#FFFFFF') >= 4.5) {
                return $c;
            }
        }

        return $colors[0];
    }

    private function isNeutral(string $hex): bool
    {
        [, $s, $l] = Palette::hsl($hex);

        return $l > 0.93 || $l < 0.09 || $s < 0.16 || ($s < 0.25 && ($l < 0.2 || $l > 0.85));
    }

    private function distinct(string $a, string $b): bool
    {
        [$h1] = Palette::hsl($a);
        [$h2] = Palette::hsl($b);
        $hue = min(abs($h1 - $h2), 360 - abs($h1 - $h2));

        return $hue >= 25 || $this->distance($a, $b) >= 110;
    }

    /** Weighted RGB distance ("redmean"), good enough for grouping logo colours. */
    private function distance(string $a, string $b): float
    {
        [$r1, $g1, $b1] = Palette::rgb($a);
        [$r2, $g2, $b2] = Palette::rgb($b);
        $rm = ($r1 + $r2) / 2;

        return sqrt((2 + $rm / 256) * ($r1 - $r2) ** 2 + 4 * ($g1 - $g2) ** 2 + (2 + (255 - $rm) / 256) * ($b1 - $b2) ** 2);
    }
}
