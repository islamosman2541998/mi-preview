<?php

namespace App\Services\Stock;

use App\Enums\MediaSource;
use Illuminate\Support\Facades\Cache;

/**
 * Pixabay API (https://pixabay.com/api/docs/).
 *
 * Their terms fit the platform: results cached for 24h, no permanent hotlinking
 * (we download picked photos), and "show where the images come from" next to
 * search results (see attribution()). A standard key gets up to 1280 px images.
 */
class Pixabay extends StockProvider
{
    private const API = 'https://pixabay.com/api/';

    public function key(): string
    {
        return 'pixabay';
    }

    public function label(): string
    {
        return 'Pixabay';
    }

    public function source(): MediaSource
    {
        return MediaSource::Pixabay;
    }

    public function configured(): bool
    {
        return filled(config('services.pixabay.key'));
    }

    public function setupHint(): string
    {
        return 'أضف PIXABAY_API_KEY في ملف ‎.env لتفعيل البحث في Pixabay (مفتاح مجاني فوري من pixabay.com/api/docs).';
    }

    public function attribution(): string
    {
        return 'الصور من Pixabay ومجانية للاستخدام التجاري، وتُحفظ في مكتبة الموقع مع اسم صاحبها.';
    }

    protected function fetchSearch(string $query, int $page, ?string $orientation, int $perPage): array
    {
        $json = $this->api(array_filter([
            'q' => $query,
            'image_type' => 'photo',
            'orientation' => match ($orientation) {
                'landscape' => 'horizontal',
                'portrait' => 'vertical',
                default => null, // Pixabay has no "square"
            },
            'safesearch' => 'true',
            'page' => $page,
            'per_page' => max(3, min($perPage, 200)),
        ]));

        $total = (int) ($json['totalHits'] ?? 0); // the reachable hits, not "total"

        return [
            'photos' => array_map(fn ($p) => $this->normalise($p), $json['hits'] ?? []),
            'total' => $total,
            'has_more' => $page * $perPage < $total,
        ];
    }

    protected function fetchPhoto(int $id): array
    {
        // image URLs from the API are temporary — fetch them fresh (short cache) at import time
        $json = Cache::remember("stock:pixabay:photo:{$id}", now()->addHours(6), fn () => $this->api(['id' => $id]));
        $raw = $json['hits'][0] ?? throw new \RuntimeException('الصورة لم تعد متاحة على Pixabay.');

        return [
            'photo' => $this->normalise($raw),
            'download' => (string) ($raw['largeImageURL'] ?? $raw['webformatURL'] ?? ''),
            'allowed_host' => 'pixabay.com',
        ];
    }

    protected function credit(array $photo): string
    {
        return "Image by {$photo['photographer']} from Pixabay";
    }

    protected function looksLikeKeyError(int $status, string $body): bool
    {
        // Pixabay answers a bad key with "400 [ERROR 400] Invalid or missing API key"
        return $status !== 400 || stripos($body, 'key') !== false;
    }

    private function api(array $query): array
    {
        return $this->getJson(self::API, ['key' => (string) config('services.pixabay.key'), 'lang' => 'en'] + $query);
    }

    private function normalise(array $p): array
    {
        return [
            'id' => (int) $p['id'],
            'width' => (int) ($p['imageWidth'] ?? 0),
            'height' => (int) ($p['imageHeight'] ?? 0),
            'alt' => (string) ($p['tags'] ?? ''),
            'avg_color' => null,
            'photographer' => (string) ($p['user'] ?? 'Pixabay'),
            'photographer_url' => isset($p['user'], $p['user_id']) ? "https://pixabay.com/users/{$p['user']}-{$p['user_id']}/" : null,
            'url' => $p['pageURL'] ?? null,
            'thumb' => $p['webformatURL'] ?? $p['previewURL'] ?? null,
            'preview' => $p['largeImageURL'] ?? null,
        ];
    }
}
