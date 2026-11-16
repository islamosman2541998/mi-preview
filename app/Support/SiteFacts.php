<?php

namespace App\Support;

use App\Enums\Locale;
use App\Enums\SectionType;
use App\Models\Site;

/**
 * Facts for the hero "signboard": explicit hero facts when the content
 * provides them, otherwise derived from the rest of the site (first
 * certification, founding year, head office, phone).
 *
 * Derived facts also carry "key": the icon that suits them.
 *
 * @return list<array{label: string, value: string, key?: string}>
 */
class SiteFacts
{
    public static function for(Site $site, Locale $locale, array $hero = []): array
    {
        if (! empty($hero['facts']) && is_array($hero['facts'])) {
            return array_values(array_filter(
                $hero['facts'],
                fn ($f) => filled($f['label'] ?? null) && filled($f['value'] ?? null),
            ));
        }

        $sections = $site->relationLoaded('sections') ? $site->sections : $site->sections()->get();
        $first = fn (SectionType $type) => $sections->first(fn ($s) => $s->type === $type)?->contentFor($locale) ?? [];

        $facts = [];

        $cert = $first(SectionType::Certifications)['items'][0]['name'] ?? null;
        if (filled($cert)) {
            $facts[] = ['key' => 'award', 'label' => __('preview.facts.classification'), 'value' => $cert];
        }

        $since = $first(SectionType::About)['since'] ?? null;
        if (filled($since)) {
            $facts[] = ['key' => 'clock', 'label' => __('preview.facts.since'), 'value' => (string) $since];
        }

        $address = $site->address($locale);
        if (filled($address)) {
            // the area is enough on a signboard: last comma-separated part
            $parts = array_map('trim', preg_split('/[،,]/u', $address));
            $facts[] = ['key' => 'pin', 'label' => __('preview.facts.location'), 'value' => end($parts)];
        }

        if (filled($site->phone)) {
            $facts[] = ['key' => 'phone', 'label' => __('preview.facts.phone'), 'value' => $site->phone];
        }

        return $facts;
    }
}
