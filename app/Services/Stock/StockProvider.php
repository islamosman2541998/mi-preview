<?php

namespace App\Services\Stock;

use App\Enums\MediaSource;
use App\Models\Media;
use App\Models\Site;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * A free stock-photo source for the admin image picker.
 *
 * Search results are cached (both Pexels and Pixabay ask for it), and a picked photo is
 * downloaded once into the site's media folder — previews never hotlink, and the export
 * can zip everything. Credits are kept on the media row.
 *
 * Normalised photo shape (search results and the picker):
 *   id, width, height, alt, avg_color, photographer, photographer_url, url, thumb, preview
 */
abstract class StockProvider
{
    /** Short key used in URLs, picks and file names ("pexels", "pixabay"). */
    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function source(): MediaSource;

    abstract public function configured(): bool;

    /** Arabic hint shown when the provider has no API key. */
    abstract public function setupHint(): string;

    /** Line shown under search results (where the photos come from). */
    abstract public function attribution(): string;

    /**
     * @return array{photos: list<array>, total: int, has_more: bool}
     */
    abstract protected function fetchSearch(string $query, int $page, ?string $orientation, int $perPage): array;

    /**
     * @return array{photo: array, download: string, allowed_host: string} normalised photo + file URL
     */
    abstract protected function fetchPhoto(int $id): array;

    abstract protected function credit(array $photo): string;

    /**
     * @return array{photos: list<array>, total: int, page: int, has_more: bool}
     */
    public function search(string $query, int $page = 1, ?string $orientation = null): array
    {
        $query = trim(mb_substr($query, 0, 100));
        $page = max(1, min($page, 50));
        $orientation = in_array($orientation, ['landscape', 'portrait', 'square'], true) ? $orientation : null;
        $perPage = (int) config('preview.media.stock_per_page', 24);

        if ($query === '') {
            return ['photos' => [], 'total' => 0, 'page' => 1, 'has_more' => false];
        }

        $this->ensureConfigured();

        $cacheKey = "stock:{$this->key()}:search:".md5(json_encode([$query, $page, $orientation, $perPage]));

        return Cache::remember($cacheKey, now()->addHours((int) config('preview.media.stock_cache_hours', 24)), function () use ($query, $page, $orientation, $perPage) {
            return ['page' => $page] + $this->fetchSearch($query, $page, $orientation, $perPage);
        });
    }

    /** Download a photo into the site's media library (idempotent per site). */
    public function import(Site $site, int $id): Media
    {
        $path = "sites/{$site->getKey()}/media/{$this->key()}-{$id}.jpg";

        if ($existing = $site->media()->where('path', $path)->first()) {
            return $existing;
        }

        $this->ensureConfigured();
        ['photo' => $photo, 'download' => $url, 'allowed_host' => $host] = $this->fetchPhoto($id);

        $urlHost = (string) parse_url($url, PHP_URL_HOST);
        if ($urlHost !== $host && ! str_ends_with($urlHost, '.'.$host)) {
            throw new RuntimeException("رابط صورة غير متوقع من {$this->label()}.");
        }

        try {
            $response = Http::timeout(40)->get($url);
        } catch (ConnectionException) {
            throw new RuntimeException("تعذّر تحميل الصورة من {$this->label()}، حاول مرة أخرى.");
        }

        $body = $response->body();

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/') || strlen($body) > 10 * 1024 * 1024) {
            throw new RuntimeException("تعذّر تحميل الصورة من {$this->label()}، حاول مرة أخرى.");
        }

        Storage::disk('public')->put($path, $body, 'public');

        return $site->media()->create([
            'path' => $path,
            'source' => $this->source(),
            'credit' => $this->credit($photo),
            'alt_en' => $photo['alt'] ? mb_substr($photo['alt'], 0, 255) : null,
            'meta' => ['provider' => $this->key()] + array_intersect_key($photo, array_flip(['id', 'photographer', 'photographer_url', 'url', 'avg_color', 'width', 'height'])),
        ]);
    }

    protected function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException($this->setupHint());
        }
    }

    /** GET JSON with friendly Arabic errors. */
    protected function getJson(string $url, array $query = [], array $headers = []): array
    {
        try {
            $response = Http::withHeaders($headers)->acceptJson()->timeout(15)->get($url, $query);
        } catch (ConnectionException) {
            throw new RuntimeException("تعذّر الاتصال بـ {$this->label()}، حاول مرة أخرى.");
        }

        if (in_array($response->status(), [400, 401, 403], true) && $this->looksLikeKeyError($response->status(), $response->body())) {
            throw new RuntimeException("مفتاح {$this->label()} غير صالح — راجع ملف ‎.env.");
        }

        if ($response->status() === 429) {
            throw new RuntimeException("تم تجاوز حد طلبات {$this->label()}، حاول بعد قليل.");
        }

        if (! $response->successful()) {
            throw new RuntimeException("{$this->label()} لم يستجب كما ينبغي (HTTP {$response->status()}).");
        }

        return (array) $response->json();
    }

    protected function looksLikeKeyError(int $status, string $body): bool
    {
        return $status !== 400;
    }
}
