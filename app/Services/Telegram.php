<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Minimal Telegram Bot API client (sendMessage only). */
class Telegram
{
    public function isConfigured(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.chat_id'));
    }

    /**
     * @throws RuntimeException when Telegram rejects the message or is unreachable
     */
    public function send(string $html): void
    {
        if (! $this->isConfigured()) {
            Log::info('Telegram not configured — message skipped.', ['text' => $html]);

            return;
        }

        $response = Http::timeout(8)
            ->connectTimeout(4)
            ->asForm()
            ->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
                'chat_id' => config('services.telegram.chat_id'),
                'text' => $html,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => 'true',
            ]);

        if (! $response->successful() || ! $response->json('ok')) {
            throw new RuntimeException('Telegram sendMessage failed: '.$response->status().' '.$response->body());
        }
    }
}
