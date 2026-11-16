<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Optional server-side fallback for logo background removal (remove.bg API).
 * The default path is the in-browser model; this exists for logos it handles badly.
 */
class RemoveBg
{
    public function configured(): bool
    {
        return filled(config('services.remove_bg.key'));
    }

    /** @return string PNG bytes */
    public function remove(string $path): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('أضف REMOVE_BG_API_KEY في ملف ‎.env لاستخدام remove.bg.');
        }

        try {
            $response = Http::withHeaders(['X-Api-Key' => (string) config('services.remove_bg.key')])
                ->timeout(60)
                ->attach('image_file', file_get_contents($path), basename($path))
                ->post('https://api.remove.bg/v1.0/removebg', [
                    'size' => config('services.remove_bg.size', 'preview'),
                    'format' => 'png',
                    'type' => 'auto',
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('تعذّر الاتصال بـ remove.bg.');
        }

        if ($response->status() === 402) {
            throw new RuntimeException('رصيد remove.bg انتهى.');
        }

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/png')) {
            $error = $response->json('errors.0.title');

            throw new RuntimeException('remove.bg رفض الصورة'.($error ? ": {$error}" : '.'));
        }

        return $response->body();
    }
}
