<?php

namespace App\Support;

use App\Models\Section;
use Illuminate\Support\Str;

/**
 * Small read helpers for section views. Section content keeps locale text and
 * locale-independent values (images, numbers) in index-aligned arrays; these
 * helpers merge them back into one row per item for templates.
 */
class SectionView
{
    /**
     * Items of a list ("items" by default): locale text merged over the shared values,
     * plus "img" = the resolved image URL (or null). Rows missing $required are skipped.
     *
     * @return list<array<string, mixed>>
     */
    public static function items(Section $section, array $c, string $key = 'items', ?string $required = null): array
    {
        $shared = $section->content['shared'][$key] ?? [];
        $rows = [];

        foreach (array_values((array) ($c[$key] ?? [])) as $i => $text) {
            $extra = is_array($shared[$i] ?? null) ? $shared[$i] : [];
            $row = array_merge($extra, is_array($text) ? $text : []);

            if ($required !== null && blank($row[$required] ?? null)) {
                continue;
            }

            $row['img'] = MediaUrl::for($extra['image'] ?? null);
            $rows[] = $row;
        }

        return $rows;
    }

    /** @return list<string> gallery image URLs */
    public static function images(Section $section): array
    {
        return array_values(array_filter(array_map(
            fn ($image) => MediaUrl::for(is_array($image) ? ($image['path'] ?? null) : $image),
            (array) ($section->content['shared']['images'] ?? []),
        )));
    }

    /** @return list<array{name: ?string, img: ?string}> */
    public static function logos(Section $section): array
    {
        $out = [];
        foreach ((array) ($section->content['shared']['logos'] ?? []) as $logo) {
            $img = MediaUrl::for($logo['image'] ?? null);
            if (filled($logo['name'] ?? null) || $img) {
                $out[] = ['name' => $logo['name'] ?? null, 'img' => $img];
            }
        }

        return $out;
    }

    /** @return list<array{name: ?string, dishes: list<array{name: ?string, description: ?string, price: mixed}>}> */
    public static function menu(Section $section, array $c): array
    {
        $prices = $section->content['shared']['prices'] ?? [];
        $out = [];

        foreach (array_values((array) ($c['categories'] ?? [])) as $ci => $category) {
            $dishes = [];
            foreach (array_values((array) ($category['items'] ?? [])) as $ii => $dish) {
                if (filled($dish['name'] ?? null)) {
                    $dishes[] = ['name' => $dish['name'], 'description' => $dish['description'] ?? null, 'price' => $prices[$ci][$ii] ?? null];
                }
            }
            if ($dishes || filled($category['name'] ?? null)) {
                $out[] = ['name' => $category['name'] ?? null, 'dishes' => $dishes];
            }
        }

        return $out;
    }

    /** Figures are shown with Western digits (as in the rest of the templates). */
    public static function number(mixed $value): string
    {
        return is_numeric($value) ? number_format((float) $value) : (string) ($value ?? '');
    }

    /** First letter of a name, for monogram placeholders when a section has no photo. */
    public static function initial(?string $name): string
    {
        $name = trim((string) $name);

        return $name === '' ? '' : Str::upper(mb_substr($name, 0, 1));
    }
}
