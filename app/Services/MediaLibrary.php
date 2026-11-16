<?php

namespace App\Services;

use App\Enums\MediaSource;
use App\Models\Media;
use App\Models\Section;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;

/**
 * Keeps the per-site media library in step with what sections actually use:
 * images uploaded straight into a section field get a library row too, so they
 * can be reused elsewhere and end up in the export.
 */
class MediaLibrary
{
    private const IMAGE = '/\.(jpe?g|png|webp|gif|avif|svg)$/i';

    public function registerSection(Section $section): void
    {
        $site = $section->page?->site;

        if (! $site) {
            return;
        }

        $this->register($site, $this->paths($section->content['shared'] ?? []));
    }

    /** @param  list<string>  $paths */
    public function register(Site $site, array $paths): void
    {
        $prefix = "sites/{$site->getKey()}/";

        $paths = array_values(array_unique(array_filter(
            $paths,
            fn ($p) => str_starts_with($p, $prefix) && preg_match(self::IMAGE, $p),
        )));

        if ($paths === []) {
            return;
        }

        $known = $site->media()->whereIn('path', $paths)->pluck('path')->all();

        foreach (array_diff($paths, $known) as $path) {
            if (Storage::disk('public')->exists($path)) {
                $site->media()->create(['path' => $path, 'source' => MediaSource::Upload]);
            }
        }
    }

    /** Whether any section of the site still points at this media file. */
    public function isUsed(Media $media): bool
    {
        // content is JSON with escaped slashes, so compare decoded values rather than LIKE
        return (bool) $media->site?->sections()->get(['sections.id', 'sections.content'])
            ->contains(fn (Section $s) => in_array($media->path, $this->paths($s->content['shared'] ?? []), true));
    }

    /** @return list<string> every string leaf of the shared (locale-independent) content */
    private function paths(array $shared): array
    {
        $out = [];
        array_walk_recursive($shared, function ($v) use (&$out) {
            if (is_string($v)) {
                $out[] = $v;
            }
        });

        return $out;
    }
}
