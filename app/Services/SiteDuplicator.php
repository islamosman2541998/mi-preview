<?php

namespace App\Services;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

/** Deep copy of a site (pages → sections, nav with its one-level tree, media rows) under a new slug. */
class SiteDuplicator
{
    public function duplicate(Site $source, string $slug, ?string $nameAr = null, ?string $nameEn = null): Site
    {
        return DB::transaction(function () use ($source, $slug, $nameAr, $nameEn) {
            $copy = $source->replicate(['sent_at', 'first_viewed_at', 'last_viewed_at', 'views_count']);
            $copy->fill([
                'slug' => $slug,
                'status' => SiteStatus::Draft,
                'company_name_ar' => $nameAr ?: $source->company_name_ar,
                'company_name_en' => $nameEn ?: $source->company_name_en,
                'views_count' => 0,
            ]);
            $copy->save();

            foreach ($source->pages()->with('sections')->get() as $page) {
                $newPage = $copy->pages()->create($page->only(['slug', 'title_ar', 'title_en', 'is_home', 'sort', 'is_published']));

                foreach ($page->sections as $section) {
                    $newPage->sections()->create($section->only(['type', 'variant', 'content', 'sort', 'is_visible']));
                }
            }

            $idMap = [];
            $items = $source->navItems()->get()->sortBy(fn ($i) => $i->parent_id === null ? 0 : 1);
            foreach ($items as $item) {
                $new = $copy->navItems()->create(array_merge(
                    $item->only(['label_ar', 'label_en', 'type', 'target', 'sort', 'is_cta']),
                    ['parent_id' => $item->parent_id ? ($idMap[$item->parent_id] ?? null) : null],
                ));
                $idMap[$item->id] = $new->id;
            }

            foreach ($source->media as $media) {
                $copy->media()->create($media->only(['path', 'source', 'credit', 'alt_ar', 'alt_en']));
            }

            return $copy;
        });
    }
}
