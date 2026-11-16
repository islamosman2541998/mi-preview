<?php

namespace App\Providers;

use App\Ai\AiProviders;
use App\Support\ClientContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiProviders::class);
    }

    public function boot(): void
    {
        // all tracking endpoints together, per real visitor IP (behind Cloudflare)
        RateLimiter::for('tracking', fn (Request $request) => Limit::perMinute(
            (int) config('preview.tracking.throttle_per_minute', 90)
        )->by('t|'.ClientContext::from($request)->ip()));
    }
}
