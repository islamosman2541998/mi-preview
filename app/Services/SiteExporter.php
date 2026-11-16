<?php

namespace App\Services;

use App\Models\Media;
use App\Models\NavItem;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Support\Fonts;
use App\Support\Palette;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Exports a preview site as a zip to seed the client's real site:
 *
 *   site.json   everything needed to rebuild it (identity, palette + shades, fonts, contact,
 *               SEO, pages → sections with ar/en content, navigation tree, media list)
 *   media/      every image the site uses (logos, section images, library), with the paths
 *               inside site.json rewritten to "media/<file>"
 *   README.md   the format, for whoever builds the real site
 *
 * Images come from the public disk; any remote image URL still referenced is downloaded
 * (or kept as a URL and listed under "missing" if that fails).
 */
class SiteExporter
{
    public const FORMAT = 'mi-preview-export';

    public const VERSION = 1;

    private const IMAGE = '/\.(jpe?g|png|webp|gif|avif|svg)$/i';

    /** original path/URL => "media/<file>" */
    private array $files = [];

    /** "media/<file>" => absolute local path or remote URL */
    private array $sources = [];

    private array $missing = [];

    /** Build the zip and return its absolute path (in storage/app/private/exports). */
    public function export(Site $site): string
    {
        $this->files = $this->sources = $this->missing = [];

        $site->loadMissing(['industry', 'pages.sections', 'navItems', 'media']);
        $data = $this->data($site);

        $dir = Storage::disk('local')->path('exports');
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("Cannot create {$dir}");
        }

        // unique per run: two exports in the same second must not share (or lock) a file
        $path = $dir.DIRECTORY_SEPARATOR.$site->slug.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.zip';
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create the export zip.');
        }

        foreach ($this->sources as $name => $source) {
            if (is_file($source)) {
                $zip->addFile($source, $name);

                continue;
            }

            // remote image: download it now so the export is self-contained
            try {
                $response = Http::timeout(20)->get($source);
                if ($response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                    $zip->addFromString($name, $response->body());

                    continue;
                }
            } catch (Throwable) {
                // fall through
            }
            // not in the zip: keep the original URL in site.json and report it
            $this->missing[] = $source;
            unset($this->files[array_search($name, $this->files, true)]);
        }

        $data['missing_media'] = array_values(array_unique($this->missing));
        $zip->addFromString('site.json', json_encode($this->rewrite($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('README.md', $this->readme($site));
        $zip->close();

        return $path;
    }

    /** Suggested download name. */
    public function filename(Site $site): string
    {
        return $site->slug.'-export-'.now()->format('Y-m-d').'.zip';
    }

    // ------------------------------------------------------------------ data

    private function data(Site $site): array
    {
        $fonts = Fonts::pairing($site);

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'source' => ['preview_url' => $site->url(), 'template' => $site->template],
            'site' => [
                'slug' => $site->slug,
                'name' => ['ar' => $site->company_name_ar, 'en' => $site->company_name_en],
                'industry' => $site->industry ? ['key' => $site->industry->key, 'ar' => $site->industry->name_ar, 'en' => $site->industry->name_en] : null,
                'description' => $site->short_description,
                'default_locale' => $site->default_locale?->value ?? 'ar',
                'logo' => $this->file($site->logo_path),
                'logo_transparent' => $this->file($site->logo_nobg_path),
                'palette' => Palette::withShades(Palette::base($site)),
                'css_variables' => Palette::cssVariables($site),
                'fonts' => [
                    'heading' => $fonts['heading']['family'],
                    'body' => $fonts['body']['family'],
                    'google_fonts_url' => Fonts::stylesheetUrl($site),
                ],
                'contact' => [
                    'phone' => $site->phone,
                    'whatsapp' => $site->whatsapp,
                    'email' => $site->email,
                    'address' => ['ar' => $site->address_ar, 'en' => $site->address_en],
                    'map_url' => $site->map_url,
                ],
                'socials' => array_filter((array) $site->socials),
                'seo' => [
                    'title' => ['ar' => $site->seo['title_ar'] ?? null, 'en' => $site->seo['title_en'] ?? null],
                    'description' => ['ar' => $site->seo['description_ar'] ?? null, 'en' => $site->seo['description_en'] ?? null],
                ],
            ],
            'pages' => $site->pages->map(fn (Page $page) => [
                'slug' => $page->slug,
                'title' => ['ar' => $page->title_ar, 'en' => $page->title_en],
                'is_home' => (bool) $page->is_home,
                'is_published' => (bool) $page->is_published,
                'sort' => $page->sort,
                'sections' => $page->sections->map(fn (Section $s) => [
                    'type' => $s->type->value,
                    'variant' => $s->variant,
                    'anchor' => $s->anchor(),
                    'is_visible' => (bool) $s->is_visible,
                    'sort' => $s->sort,
                    'content' => $this->content($s),
                ])->values()->all(),
            ])->values()->all(),
            'navigation' => $this->nav($site),
            'media' => $site->media->map(fn (Media $m) => [
                'file' => $this->file($m->path),
                'source' => $m->source->value,
                'credit' => $m->credit,
                'alt' => ['ar' => $m->alt_ar, 'en' => $m->alt_en],
                'meta' => $m->meta,
            ])->filter(fn ($m) => $m['file'])->values()->all(),
        ];
    }

    /** Section content without internal AI bookkeeping; image paths are collected for the zip. */
    private function content(Section $section): array
    {
        $content = $section->content ?? [];
        unset($content['ai']);

        $shared = (array) ($content['shared'] ?? []);
        array_walk_recursive($shared, function ($value) {
            if (is_string($value)) {
                $this->file($value);
            }
        });

        return $content;
    }

    private function nav(Site $site): array
    {
        $items = $site->navItems;
        $map = fn (NavItem $item) => [
            'label' => ['ar' => $item->label_ar, 'en' => $item->label_en],
            'type' => $item->type->value,
            'target' => $item->target,
            'is_cta' => (bool) $item->is_cta,
            'sort' => $item->sort,
        ];

        return $items->whereNull('parent_id')->map(fn (NavItem $item) => $map($item) + [
            'children' => $items->where('parent_id', $item->id)->map($map)->values()->all(),
        ])->values()->all();
    }

    /**
     * Register an image for the zip and return its name inside it ("media/…"),
     * or null when the value isn't an image we can ship.
     */
    private function file(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (isset($this->files[$path])) {
            return $this->files[$path];
        }

        $remote = (bool) preg_match('#^https?://#i', $path);

        // only images (a map embed or link in the content is not media)
        if (! preg_match(self::IMAGE, parse_url($path, PHP_URL_PATH) ?: $path)) {
            return null;
        }

        if ($remote) {
            $source = $path;
        } elseif (Storage::disk('public')->exists($path)) {
            $source = Storage::disk('public')->path($path);
        } else {
            $this->missing[] = $path;

            return null;
        }

        $base = Str::slug(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_FILENAME)) ?: 'image';
        $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION)) ?: 'jpg';
        $name = "media/{$base}.{$ext}";
        for ($i = 2; isset($this->sources[$name]); $i++) {
            $name = "media/{$base}-{$i}.{$ext}";
        }

        $this->sources[$name] = $source;

        return $this->files[$path] = $name;
    }

    /** Replace every original image path in the data with its name inside the zip. */
    private function rewrite(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value) && isset($this->files[$value])) {
                $value = $this->files[$value];
            }
        });

        return $data;
    }

    private function readme(Site $site): string
    {
        $name = $site->company_name_en ?: $site->company_name_ar;

        return <<<MD
# {$name} — content export

Exported from the MI Web Development preview platform ({$site->url()}).

## Files

- `site.json` — format `mi-preview-export`, version 1.
- `media/` — every image the site uses. Paths inside `site.json` point here (`media/…`).

## site.json

| Key | What it holds |
|---|---|
| `site` | names (ar/en), industry, contact, socials, SEO, `palette` (5 base colours + 50–900 shades), `css_variables` used by the preview, `fonts` (+ Google Fonts URL), logos |
| `pages[]` | `slug`, `title` {ar,en}, `is_home`, `is_published`, `sections[]` in display order |
| `pages[].sections[]` | `type`, `variant`, `anchor` (DOM id used by "#anchor" links), `is_visible`, `content` |
| `navigation[]` | menu items (`type`: page / anchor / external / whatsapp / phone, `target`), one level of `children` |
| `media[]` | library images with `credit` (keep Pixabay/Pexels credits) and `alt` {ar,en} |
| `missing_media` | images that could not be included (re-upload them) |

### Section content

`content` = `{ "ar": {...}, "en": {...}, "shared": {...} }`. Text lives under `ar` / `en`;
locale-independent values (images, numbers, links) under `shared`. Lists are aligned by
index across the three: `ar.items[2]`, `en.items[2]` and `shared.items[2]` describe the same item.
To render a locale: merge `shared` with that locale's object (locale keys win).

Section types: hero, about, services, projects, gallery, stats, testimonials, clients, faq,
cta, contact, map, menu, booking, products, certifications.
MD;
    }
}
