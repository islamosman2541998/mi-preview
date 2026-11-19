<?php

namespace App\Services;

use App\Enums\SectionType;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Expands the starter blueprint using the actual business and social-profile data.
 * Existing pages and sections are preserved; the planner only adds useful missing
 * structure, so running whole-site generation again is safe and idempotent.
 */
class SiteStructurePlanner
{
    private const PAGE_TITLES = [
        'about' => ['ar' => 'من نحن', 'en' => 'About'],
        'services' => ['ar' => 'خدماتنا', 'en' => 'Services'],
        'projects' => ['ar' => 'أعمالنا ومشاريعنا', 'en' => 'Work & Projects'],
        'products' => ['ar' => 'المنتجات', 'en' => 'Products'],
        'menu' => ['ar' => 'القائمة', 'en' => 'Menu'],
        'contact' => ['ar' => 'تواصل معنا', 'en' => 'Contact'],
    ];

    public function enrich(Site $site): bool
    {
        $home = $site->pages()->where('is_home', true)->first();

        if (! $home) {
            return false;
        }

        $source = (array) $site->source_data;
        $posts = array_values((array) ($source['posts'] ?? []));
        $imageCount = count(array_filter($posts, fn (array $post): bool => filled($post['image_url'] ?? null)));
        $text = Str::lower(implode(' ', array_filter([
            $site->short_description,
            $source['biography'] ?? null,
            ...array_map(fn (array $post) => (string) ($post['caption'] ?? ''), $posts),
        ])));

        $blueprintTypes = collect($site->industry?->sections_blueprint['pages'] ?? [])
            ->flatMap(fn (array $page) => $page['sections'] ?? [])
            ->map(fn ($section) => is_array($section) ? ($section['type'] ?? null) : $section)
            ->filter()
            ->values()
            ->all();

        $hasBlueprint = fn (SectionType $type): bool => in_array($type->value, $blueprintTypes, true);
        $mentions = fn (array $words): bool => Str::contains($text, $words);
        $desired = [SectionType::Hero, SectionType::About];

        $specialOffering = match (true) {
            $hasBlueprint(SectionType::Menu) || $mentions(['menu', 'restaurant', 'cafe', 'منيو', 'مطعم', 'كافيه']) => SectionType::Menu,
            $hasBlueprint(SectionType::Products) || $mentions(['products', 'shop', 'store', 'منتجات', 'متجر']) => SectionType::Products,
            default => SectionType::Services,
        };
        $desired[] = $specialOffering;

        $projectBusiness = in_array($site->industry?->key, ['contracting', 'fire_safety', 'real_estate'], true)
            || $hasBlueprint(SectionType::Projects)
            || $mentions(['project', 'portfolio', 'our work', 'completed', 'installation', 'fit-out', 'مشروع', 'مشاريع', 'أعمالنا', 'تنفيذ', 'تصميم']);

        if ($projectBusiness || ($site->industry_id === null && count($posts) >= 4)) {
            $desired[] = SectionType::Projects;
        }
        if ($hasBlueprint(SectionType::Gallery) || $imageCount >= 4) {
            $desired[] = SectionType::Gallery;
        }
        if ($hasBlueprint(SectionType::Stats) || $site->industry_id === null || count($posts) >= 8) {
            $desired[] = SectionType::Stats;
        }
        foreach ([SectionType::Certifications, SectionType::Clients] as $type) {
            if ($hasBlueprint($type)) {
                $desired[] = $type;
            }
        }
        $desired[] = SectionType::Testimonials;
        if ($hasBlueprint(SectionType::Booking)) {
            $desired[] = SectionType::Booking;
        }
        $desired[] = SectionType::Faq;
        $desired[] = SectionType::Cta;
        $desired = array_values(array_unique($desired, SORT_REGULAR));

        return DB::transaction(function () use ($site, $home, $desired): bool {
            $changed = $this->syncSections($site, $home, $desired);

            $pagePlans = [
                'about' => [SectionType::About, SectionType::Stats, SectionType::Testimonials],
                'contact' => [SectionType::Contact, SectionType::Map],
            ];
            if (in_array(SectionType::Services, $desired, true)) {
                $pagePlans['services'] = [SectionType::Services, SectionType::Faq, SectionType::Cta];
            }
            if (in_array(SectionType::Projects, $desired, true)) {
                $pagePlans['projects'] = array_values(array_filter([
                    SectionType::Projects,
                    in_array(SectionType::Gallery, $desired, true) ? SectionType::Gallery : null,
                    SectionType::Cta,
                ]));
            }
            if (in_array(SectionType::Products, $desired, true)) {
                $pagePlans['products'] = [SectionType::Products, SectionType::Faq, SectionType::Cta];
            }
            if (in_array(SectionType::Menu, $desired, true)) {
                $pagePlans['menu'] = [SectionType::Menu, SectionType::Gallery, SectionType::Cta];
            }

            foreach ($pagePlans as $slug => $types) {
                $page = $site->pages()->where('slug', $slug)->first();
                if (! $page) {
                    $title = self::PAGE_TITLES[$slug];
                    $page = $site->pages()->create([
                        'slug' => $slug,
                        'title_ar' => $title['ar'],
                        'title_en' => $title['en'],
                        'sort' => ((int) $site->pages()->max('sort')) + 1,
                    ]);
                    $changed = true;
                }
                $changed = $this->syncSections($site, $page, $types) || $changed;
            }

            $pageOrder = ['home', 'about', 'services', 'projects', 'products', 'menu', 'contact'];
            $pages = $site->pages()->get()->sortBy(function (Page $page) use ($pageOrder): int {
                $position = array_search($page->slug, $pageOrder, true);

                return $position === false ? 50 + $page->sort : $position;
            })->values();
            foreach ($pages as $sort => $page) {
                if ($page->sort !== $sort) {
                    $page->update(['sort' => $sort]);
                    $changed = true;
                }
            }

            return $changed;
        });
    }

    /** @param list<SectionType> $types */
    private function syncSections(Site $site, Page $page, array $types): bool
    {
        $changed = false;
        $existing = $page->sections()->get()->keyBy(fn ($section) => $section->type->value);

        foreach ($types as $sort => $type) {
            $section = $existing->get($type->value);
            if (! $section) {
                $section = $page->sections()->create([
                    'type' => $type,
                    'content' => SectionDefaults::for($type, $site),
                    'sort' => $sort,
                ]);
                $changed = true;
            } elseif ($section->sort !== $sort) {
                $section->update(['sort' => $sort]);
                $changed = true;
            }
        }

        $next = count($types);
        foreach ($page->sections()->whereNotIn('type', array_map(fn (SectionType $type) => $type->value, $types))->get() as $section) {
            if ($section->sort !== $next) {
                $section->update(['sort' => $next]);
                $changed = true;
            }
            $next++;
        }

        return $changed;
    }
}
