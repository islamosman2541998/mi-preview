<?php

namespace App\Support;

use App\Models\Site;

/** Resolves a site's font pairing and its Google Fonts stylesheet URL. */
class Fonts
{
    public static function pairing(Site $site): array
    {
        $fonts = config('preview.fonts', []);
        $key = $site->font_key
            ?: config("preview.templates.{$site->template}.font")
            ?: array_key_first($fonts);

        return $fonts[$key] ?? reset($fonts);
    }

    public static function stylesheetUrl(Site $site): string
    {
        $pairing = self::pairing($site);
        $families = [];

        foreach (['heading', 'body'] as $role) {
            $family = $pairing[$role]['family'];
            $weights = $families[$family] ?? [];
            $families[$family] = array_unique([...$weights, ...$pairing[$role]['weights']]);
        }

        $query = collect($families)->map(function ($weights, $family) {
            sort($weights);

            return 'family='.str_replace(' ', '+', $family).':wght@'.implode(';', $weights);
        })->implode('&');

        return 'https://fonts.googleapis.com/css2?'.$query.'&display=swap';
    }

    public static function cssVariables(Site $site): string
    {
        $p = self::pairing($site);
        $stack = 'system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif';

        $fallback = fn (array $role) => ($role['fallback'] ?? null) === 'serif' ? '"Times New Roman", serif' : $stack;

        return "--f-heading:\"{$p['heading']['family']}\", {$fallback($p['heading'])};--f-body:\"{$p['body']['family']}\", {$fallback($p['body'])}";
    }
}
