<?php

namespace App\Services;

use App\Enums\NavItemType;
use App\Enums\SectionType;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * Builds a site's pages → sections from its industry blueprint (rules, not
 * AI), then auto-generates the navbar. Used by the seeders now and by the
 * admin create-wizard later.
 */
class SiteBuilder
{
    /** Used when the site has no industry (or the industry has no blueprint). */
    public const DEFAULT_BLUEPRINT = [
        'pages' => [
            ['slug' => 'home', 'title' => ['ar' => 'الرئيسية', 'en' => 'Home'], 'is_home' => true,
                'sections' => ['hero', 'about', 'services', 'stats', 'testimonials', 'cta']],
            ['slug' => 'contact', 'title' => ['ar' => 'تواصل معنا', 'en' => 'Contact'],
                'sections' => ['contact', 'map']],
        ],
    ];

    /** Home-page sections that get an anchor link when the site is single-page. */
    private const ANCHOR_TYPES = ['about', 'services', 'projects', 'menu', 'products', 'booking', 'faq', 'contact'];

    /**
     * @param  array<string, array>  $content  overrides keyed "{page}.{type}" or "{type}"
     */
    public function build(Site $site, ?array $blueprint = null, array $content = []): Site
    {
        if ($site->pages()->exists()) {
            throw new LogicException("Site [{$site->slug}] already has pages.");
        }

        $blueprint ??= $site->industry?->sections_blueprint ?: self::DEFAULT_BLUEPRINT;

        DB::transaction(function () use ($site, $blueprint, $content) {
            $pages = $blueprint['pages'] ?? [];
            $hasHome = collect($pages)->contains(fn ($p) => ! empty($p['is_home']));

            foreach (array_values($pages) as $i => $def) {
                $page = $site->pages()->create([
                    'slug' => $def['slug'],
                    'title_ar' => $def['title']['ar'] ?? $def['slug'],
                    'title_en' => $def['title']['en'] ?? null,
                    'is_home' => ! empty($def['is_home']) || (! $hasHome && $i === 0),
                    'sort' => $i,
                ]);

                $this->buildSections($site, $page, $def['sections'] ?? [], $content);
            }

            $this->generateNav($site);
        });

        return $site->refresh();
    }

    /** (Re)generates nav items from the site's published pages. */
    public function generateNav(Site $site): void
    {
        DB::transaction(function () use ($site) {
            $site->navItems()->delete();

            $pages = $site->pages()->published()->get();
            $home = $pages->firstWhere('is_home', true);
            $sort = 0;

            foreach ($pages as $page) {
                $site->navItems()->create([
                    'label_ar' => $page->title_ar,
                    'label_en' => $page->title_en,
                    'type' => NavItemType::Page,
                    'target' => $page->slug,
                    'sort' => $sort++,
                ]);
            }

            // Single-page site: link to the home sections instead.
            if ($home && $pages->count() === 1) {
                foreach ($home->sections()->where('is_visible', true)->get() as $section) {
                    if (! in_array($section->type->value, self::ANCHOR_TYPES, true)) {
                        continue;
                    }

                    $site->navItems()->create([
                        'label_ar' => $section->type->getLabel(),
                        'label_en' => str($section->type->value)->headline()->toString(),
                        'type' => NavItemType::Anchor,
                        'target' => $section->anchor(),
                        'sort' => $sort++,
                    ]);
                }
            }

            $cta = match (true) {
                filled($site->whatsapp) => [NavItemType::Whatsapp, 'تواصل واتساب', 'WhatsApp us'],
                filled($site->phone) => [NavItemType::Phone, 'اتصل بنا', 'Call us'],
                default => null,
            };

            if ($cta) {
                $site->navItems()->create([
                    'label_ar' => $cta[1],
                    'label_en' => $cta[2],
                    'type' => $cta[0],
                    'target' => null, // empty → the site's own number
                    'sort' => $sort,
                    'is_cta' => true,
                ]);
            }
        });
    }

    private function buildSections(Site $site, Page $page, array $sections, array $content): void
    {
        foreach (array_values($sections) as $i => $def) {
            $def = is_string($def) ? ['type' => $def] : $def;
            $type = SectionType::tryFrom($def['type'] ?? '')
                ?? throw new InvalidArgumentException('Unknown section type ['.($def['type'] ?? '').'].');

            $page->sections()->create([
                'type' => $type,
                'variant' => $def['variant'] ?? null,
                'content' => $content["{$page->slug}.{$type->value}"]
                    ?? $content[$type->value]
                    ?? SectionDefaults::for($type, $site),
                'sort' => $i,
            ]);
        }
    }
}
