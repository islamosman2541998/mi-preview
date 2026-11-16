<?php

namespace App\Support;

use App\Enums\Locale;
use App\Enums\SectionType;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Site-wide lookups templates need outside the section being rendered: the photo
 * behind an inner page's title, the service names for the footer and the contact
 * form, the page that holds "all projects", and so on. Reads the site's sections
 * once per request.
 */
class PageVisuals
{
    /**
     * Photo behind an inner page's title: the home hero photo (one consistent banner
     * across the site), otherwise the first photo found on the page itself.
     *
     * @param  Collection<int, Section>  $sections  the page's visible sections
     */
    public static function banner(Site $site, Page $page, Collection $sections): ?string
    {
        $hero = self::first($site, SectionType::Hero)?->content['shared']['image'] ?? null;

        return MediaUrl::for($hero) ?? $sections->map(fn (Section $s) => self::firstPhoto($s))->filter()->first();
    }

    /** @return list<string> service names (first services section), for the footer and forms */
    public static function services(Site $site, Locale $locale, int $limit = 6): array
    {
        $items = self::first($site, SectionType::Services)?->contentFor($locale)['items'] ?? [];

        return collect($items)->pluck('title')->filter()->take($limit)->values()->all();
    }

    /** A short "about" paragraph for the footer. */
    public static function aboutSnippet(Site $site, Locale $locale, int $limit = 170): ?string
    {
        $body = self::first($site, SectionType::About)?->contentFor($locale)['body'] ?? null;

        return filled($body) ? Str::limit(trim(preg_replace('/\s+/u', ' ', $body)), $limit) : null;
    }

    public static function hours(Site $site, Locale $locale): ?string
    {
        $hours = self::first($site, SectionType::Contact)?->contentFor($locale)['hours'] ?? null;

        return filled($hours) ? $hours : null;
    }

    /**
     * Another published page that carries this section type ("see all projects"),
     * or null when the only one is the page being rendered.
     */
    public static function pageWith(Site $site, SectionType $type, Page $current): ?Page
    {
        $pageIds = self::sections($site)
            ->filter(fn (Section $s) => $s->type === $type && $s->is_visible && $s->page_id !== $current->id)
            ->pluck('page_id');

        return $site->pages->first(fn (Page $p) => ! $p->is_home && $pageIds->contains($p->id));
    }

    /** First visible section of a type, home page first. */
    private static function first(Site $site, SectionType $type): ?Section
    {
        $home = $site->pages->firstWhere('is_home', true)?->id;

        return self::sections($site)
            ->filter(fn (Section $s) => $s->type === $type && $s->is_visible)
            ->sortBy(fn (Section $s) => [$s->page_id === $home ? 0 : 1, $s->sort, $s->id])
            ->first();
    }

    /** @return Collection<int, Section> */
    private static function sections(Site $site): Collection
    {
        return $site->loadMissing('sections')->sections;
    }

    private static function firstPhoto(Section $section): ?string
    {
        $shared = $section->content['shared'] ?? [];

        $candidates = [
            $shared['image'] ?? null,
            ...array_map(fn ($image) => is_array($image) ? ($image['path'] ?? null) : $image, (array) ($shared['images'] ?? [])),
            ...array_column((array) ($shared['items'] ?? []), 'image'),
        ];

        foreach ($candidates as $path) {
            if ($url = MediaUrl::for(is_string($path) ? $path : null)) {
                return $url;
            }
        }

        return null;
    }
}
