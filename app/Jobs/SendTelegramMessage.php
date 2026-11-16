<?php

namespace App\Jobs;

use App\Services\Telegram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Alerts are first attempted right after the HTTP response is sent (so they
 * arrive instantly — the cron queue worker only runs once a minute). If that
 * attempt fails, the message is pushed onto the database queue for retries.
 */
class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public array $backoff = [30, 120, 600];

    public function __construct(public string $text) {}

    /**
     * Send now-ish: after the response in web requests, inline in console.
     * Calls handle() directly (not dispatchSync/afterResponse, which run
     * through the sync queue) so a failure really falls back to the queue.
     */
    public static function sendSoon(string $text): void
    {
        $send = fn () => (new static($text))->handle(app(Telegram::class));

        app()->runningInConsole() ? $send() : app()->terminating($send);
    }

    public function handle(Telegram $telegram): void
    {
        try {
            $telegram->send($this->text);
        } catch (Throwable $e) {
            if ($this->job !== null) {
                throw $e; // already on the queue: let the worker retry with backoff
            }

            report($e);
            $this->fallBackToQueue();
        }
    }

    private function fallBackToQueue(): void
    {
        try {
            static::dispatch($this->text)->delay(now()->addSeconds(30));
        } catch (Throwable $e) {
            Log::error('Telegram alert lost: queue unavailable.', ['text' => $this->text, 'error' => $e->getMessage()]);
        }
    }
}
