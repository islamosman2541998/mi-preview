<?php

namespace App\Services\Stock;

use App\Enums\MediaSource;
use Illuminate\Support\Facades\Cache;

/** Pexels API (https://www.pexels.com/api/documentation/). */
class Pexels extends StockProvider
{
    private const API = 'https://api.pexels.com/v1';

    public function key(): string
    {
        return 'pexels';
    }

    public function label(): string
    {
        return 'Pexels';
    }

    public function source(): MediaSource
    {
        return MediaSource::Pexels;
    }

    public function configured(): bool
    {
        return filled(config('services.pexels.key'));
    }

    public function setupHint(): string
    {
        return 'أضف PEXELS_API_KEY في ملف ‎.env لتفعيل البحث في Pexels.';
    }

    public function attribution(): string
    {
        return 'الصور من Pexels ومجانية للاستخدام، وتُحفظ في مكتبة الموقع مع اسم المصور.';
    }

    protected function fetchSearch(string $query, int $page, ?string $orientation, int $perPage): array
    {
        $json = $this->api('/search', array_filter([
            'query' => $query,
            'page' => $page,
            'per_page' => min($perPage, 80),
            'orientation' => $orientation,
        ]));

        return [
            'photos' => array_map(fn ($p) => $this->normalise($p), $json['photos'] ?? []),
            'total' => (int) ($json['total_results'] ?? 0),
            'has_more' => filled($json['next_page'] ?? null),
        ];
    }

    protected function fetchPhoto(int $id): array
    {
        $raw = Cache::remember("stock:pexels:photo:{$id}", now()->addDay(), fn () => $this->api("/photos/{$id}"));

        // Pexels' CDN resizes and compresses on the fly
        $download = ($raw['src']['original'] ?? '').'?'.http_build_query([
            'auto' => 'compress',
            'cs' => 'tinysrgb',
            'w' => (int) config('preview.media.stock_width', 1920),
        ]);

        return ['photo' => $this->normalise($raw), 'download' => $download, 'allowed_host' => 'images.pexels.com'];
    }

    protected function credit(array $photo): string
    {
        return "Photo by {$photo['photographer']} on Pexels";
    }

    private function api(string $endpoint, array $query = []): array
    {
        return $this->getJson(self::API.$endpoint, $query, ['Authorization' => (string) config('services.pexels.key')]);
    }

    private function normalise(array $p): array
    {
        return [
            'id' => (int) $p['id'],
            'width' => (int) ($p['width'] ?? 0),
            'height' => (int) ($p['height'] ?? 0),
            'alt' => (string) ($p['alt'] ?? ''),
            'avg_color' => $p['avg_color'] ?? null,
            'photographer' => (string) ($p['photographer'] ?? 'Pexels'),
            'photographer_url' => $p['photographer_url'] ?? null,
            'url' => $p['url'] ?? null,
            'thumb' => $p['src']['medium'] ?? null,
            'preview' => $p['src']['large'] ?? null,
        ];
    }
}
