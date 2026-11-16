<?php

use App\Http\Middleware\AddNoIndexHeader;
use App\Http\Middleware\ResolvePreviewSite;
use App\Support\Cloudflare;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            Route::group([], base_path('routes/preview.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Cloudflare: honour X-Forwarded-* only from Cloudflare edges.
        $middleware->trustProxies(
            at: Cloudflare::IPS,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->append(AddNoIndexHeader::class);

        // admin helper routes use `auth`: send guests to the Filament login
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));

        // Preview sites: no session / CSRF (keeps bot hits and visitors light).
        $middleware->group('preview', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            ResolvePreviewSite::class,
            SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
