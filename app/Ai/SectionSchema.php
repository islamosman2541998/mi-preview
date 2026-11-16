<?php

namespace App\Ai;

use App\Enums\SectionType;
use App\Filament\Support\SectionFields;
use App\Support\ServiceIcons;

/**
 * JSON schemas for AI output, derived from the same per-type definitions the admin
 * forms use (SectionFields::definition), so the two can never drift.
 *
 * The AI writes text (ar + en), numbers, and stock-photo search queries. It never
 * writes images, links or embeds — those are kept from the existing section.
 *
 * Output shape per section ("content") mirrors the admin form state, which
 * SectionFields::fromForm() already turns into stored content:
 *   ar / en          {field: string}
 *   shared           {number fields}
 *   items            [{field_ar, field_en, number/suffix fields, icon?, image_query?}]
 *   lists            {key: [{ar, en}]}
 *   logos            [{name}]
 *   menu             [{name_ar, name_en, dishes: [{name_ar, name_en, description_ar, description_en, price}]}]
 *   image_query      English stock-photo search for the section's main image(s)
 *
 * The full response schema is the same for every site (an array whose items are one of
 * the 16 section variants), so the API compiles it once and caches it.
 */
class SectionSchema
{
    /** Writing guidance attached to fields (schema descriptions steer the model). */
    private const HINTS = [
        'hero.eyebrow' => 'Short line above the headline: what the company is + where (max 6 words).',
        'hero.title' => 'Headline: a specific promise for this business (max 9 words). No slogans.',
        'hero.subtitle' => 'One or two sentences (max 30 words) with concrete specifics.',
        'hero.primary_cta' => 'Button that opens WhatsApp (2–4 words).',
        'hero.secondary_cta' => 'Secondary button, e.g. to see projects/services (2–3 words).',
        'about.body' => '2 short paragraphs (60–110 words total), concrete: scope, region, how they work.',
        'about.since' => 'Founding year — use the one given in the facts, otherwise a plausible year.',
        'cta.body' => 'One sentence (max 25 words).',
        'cta.button' => '2–4 words.',
        'contact.hours' => 'Working hours, e.g. "Sat–Thu 9:00–18:00".',
        'stats.value' => 'A plausible, modest number for a company of this size.',
        'stats.suffix' => 'Optional "+" or "%" (empty string if none).',
        'faq.q' => 'A question real customers of this business ask.',
        'faq.a' => 'Direct, useful answer (max 45 words).',
        'testimonials.quote' => 'Specific, believable (max 35 words), mentions the actual service.',
        'testimonials.name' => 'First name + initial of last name.',
        'testimonials.role' => 'Their role / company type (generic, no real brand names).',
        'projects.year' => 'Completion year (recent, plausible).',
        'products.price' => 'Price as a number in the local currency, 0 if not applicable.',
        'menu.price' => 'Price as a number in the local currency.',
    ];

    /** Items per list the model should aim for. */
    private const COUNTS = [
        'services' => '4–6', 'projects' => '4–6', 'stats' => '4', 'testimonials' => '3',
        'faq' => '5–7', 'products' => '4–8', 'certifications' => '3–5', 'clients' => '6–8',
        'about.points' => '3–4', 'booking.options' => '3–6', 'menu' => '3–4 categories × 3–5 dishes',
    ];

    /**
     * Response schema for a generation call. Without $types it is identical for every site
     * (Claude compiles it once and caches it); with $types it only allows those section types
     * (smaller, for providers that limit schema complexity).
     *
     * @param  list<SectionType>|null  $types
     */
    public static function response(?array $types = null): array
    {
        $types = $types === null ? SectionType::cases() : array_values(array_unique($types, SORT_REGULAR));

        $variants = array_map(fn (SectionType $type) => self::object([
            'id' => ['type' => 'integer', 'description' => 'The section id from the request.'],
            'type' => ['type' => 'string', 'const' => $type->value],
            'content' => self::forType($type),
        ]), $types);

        return self::object([
            'seo' => self::object([
                'title_ar' => self::str('Home page <title> in Arabic (max 60 characters).'),
                'title_en' => self::str('Home page <title> in English (max 60 characters).'),
                'description_ar' => self::str('Meta description in Arabic (max 155 characters).'),
                'description_en' => self::str('Meta description in English (max 155 characters).'),
            ]),
            'sections' => ['type' => 'array', 'items' => ['anyOf' => $variants]],
        ]);
    }

    /** Schema of one section's AI content. */
    public static function forType(SectionType $type): array
    {
        $def = SectionFields::definition($type);
        $t = $type->value;
        $props = [];

        foreach (['ar' => 'Arabic', 'en' => 'English'] as $locale => $language) {
            $fields = [];
            foreach ($def['text'] ?? [] as $f => $spec) {
                $fields[$f] = self::str(trim("{$language}. ".(self::HINTS["{$t}.{$f}"] ?? '')));
            }
            $props[$locale] = self::object($fields);
        }

        $shared = [];
        foreach ($def['shared'] ?? [] as $f => $spec) {
            if ($spec[0] === 'number') {
                $shared[$f] = self::num(self::HINTS["{$t}.{$f}"] ?? '');
            }
        }
        if ($shared) {
            $props['shared'] = self::object($shared);
        }

        if ($items = $def['items'] ?? null) {
            [, , $text, $sharedFields] = $items;
            $row = [];
            foreach ($text as $f => $spec) {
                $row["{$f}_ar"] = self::str(trim('Arabic. '.(self::HINTS["{$t}.{$f}"] ?? '')));
                $row["{$f}_en"] = self::str(trim('English. '.(self::HINTS["{$t}.{$f}"] ?? '')));
            }
            foreach ($sharedFields as $f => $spec) {
                $row[$f] = match ($spec[0]) {
                    'image' => null,
                    'icon' => ['type' => 'string', 'enum' => ServiceIcons::KEYS, 'description' => 'The icon closest to this service.'],
                    'number' => self::num(self::HINTS["{$t}.{$f}"] ?? ''),
                    default => self::str(self::HINTS["{$t}.{$f}"] ?? ''),
                };
            }
            if (self::hasItemImages($type)) {
                $row['image_query'] = self::str('English stock-photo search for this item (2–5 concrete words).');
            }
            $props['items'] = [
                'type' => 'array',
                'description' => 'Aim for '.(self::COUNTS[$t] ?? '3–6').' items.',
                'items' => self::object(array_filter($row)),
            ];
        }

        if ($def['lists'] ?? null) {
            $lists = [];
            foreach ($def['lists'] as $key => $label) {
                $lists[$key] = [
                    'type' => 'array',
                    'description' => 'Aim for '.(self::COUNTS["{$t}.{$key}"] ?? '3–5').' entries.',
                    'items' => self::object(['ar' => self::str('Arabic.'), 'en' => self::str('English.')]),
                ];
            }
            $props['lists'] = self::object($lists);
        }

        if (! empty($def['logos'])) {
            $props['logos'] = [
                'type' => 'array',
                'description' => 'Aim for '.self::COUNTS['clients'].' entries. Generic descriptions of client types, never real brand names.',
                'items' => self::object(['name' => self::str('e.g. "شركة تطوير عقاري" / "Real estate developer".')]),
            ];
        }

        if (! empty($def['menu'])) {
            $props['menu'] = [
                'type' => 'array',
                'description' => 'Aim for '.self::COUNTS['menu'].'.',
                'items' => self::object([
                    'name_ar' => self::str('Category name in Arabic.'),
                    'name_en' => self::str('Category name in English.'),
                    'dishes' => ['type' => 'array', 'items' => self::object([
                        'name_ar' => self::str('Arabic.'), 'name_en' => self::str('English.'),
                        'description_ar' => self::str('Arabic, max 12 words.'), 'description_en' => self::str('English, max 12 words.'),
                        'price' => self::num(self::HINTS['menu.price']),
                    ])],
                ]),
            ];
        }

        if (self::imageFields($type) !== []) {
            $props['image_query'] = self::str('English stock-photo search for this section (2–5 concrete words, a photographable scene — no text, logos or people\'s faces up close).');
        }

        return self::object($props);
    }

    /**
     * Image fields this section can have filled from stock photos.
     *
     * @return list<string> "image" / "image2" (shared), "images" (shared gallery) or "items.image"
     */
    public static function imageFields(SectionType $type): array
    {
        return match ($type) {
            SectionType::Hero, SectionType::Stats, SectionType::Cta, SectionType::Faq, SectionType::Booking => ['image'],
            SectionType::About => ['image', 'image2'],
            SectionType::Gallery => ['images'],
            SectionType::Services, SectionType::Projects, SectionType::Products => ['items.image'],
            default => [],
        };
    }

    public static function hasItemImages(SectionType $type): bool
    {
        return in_array('items.image', self::imageFields($type), true);
    }

    // ------------------------------------------------------------------ helpers

    private static function object(array $properties): array
    {
        return [
            'type' => 'object',
            'properties' => $properties === [] ? new \stdClass : $properties,
            'required' => array_keys($properties),
            'additionalProperties' => false,
        ];
    }

    private static function str(string $description = ''): array
    {
        return array_filter(['type' => 'string', 'description' => $description]);
    }

    private static function num(string $description = ''): array
    {
        return array_filter(['type' => 'number', 'description' => $description]);
    }
}
