<?php

namespace App\Services\Stock;

use RuntimeException;

/** Registry of stock-photo providers, in picker order. */
class StockPhotos
{
    /** @var list<class-string<StockProvider>> */
    private const PROVIDERS = [Pixabay::class, Pexels::class];

    /** @return array<string, StockProvider> keyed by provider key */
    public function all(): array
    {
        $out = [];
        foreach (self::PROVIDERS as $class) {
            $provider = app($class);
            $out[$provider->key()] = $provider;
        }

        return $out;
    }

    /** @return array<string, StockProvider> providers that have an API key */
    public function configured(): array
    {
        return array_filter($this->all(), fn (StockProvider $p) => $p->configured());
    }

    public function get(string $key): StockProvider
    {
        return $this->all()[$key] ?? throw new RuntimeException('مصدر صور غير معروف.');
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }
}
