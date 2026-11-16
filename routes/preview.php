<?php

use App\Http\Controllers\PreviewPageController;
use App\Http\Controllers\TrackingController;
use App\Http\Middleware\ForceJson;
use App\Rules\SiteSlug;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Preview sites — {slug}.{base}
|--------------------------------------------------------------------------
|
| Arabic (default):  /            /{page}
| English:           /en          /en/{page}
| Tracking:          /_t/*        (t.js; throttled per IP, no session)
|
| The subdomain constraint excludes the admin subdomain and every reserved
| slug, so route registration order versus the Filament panel does not
| matter. Uses the lightweight "preview" middleware group (no session).
|
*/

$reserved = implode('|', array_map(fn ($s) => preg_quote($s, '#'), SiteSlug::reserved()));
$pageSlug = config('preview.page_slug_pattern');

Route::domain('{subdomain}.'.config('preview.base_domain'))
    ->where(['subdomain' => '(?!(?:'.$reserved.')$)[a-z0-9-]+'])
    ->middleware('preview')
    ->name('preview.')
    ->group(function () use ($pageSlug) {
        Route::prefix('_t')->controller(TrackingController::class)->group(function () {
            Route::middleware([ForceJson::class, 'throttle:tracking'])->group(function () {
                Route::post('start', 'start')->name('track.start');
                Route::post('ping', 'ping')->name('track.ping');
                Route::post('event', 'event')->name('track.event');
                Route::post('end', 'end')->name('track.end');
            });
            Route::get('owner', 'owner')->middleware('signed')->name('owner');
        });

        Route::get('/en/{page?}', PreviewPageController::class)
            ->where('page', $pageSlug)
            ->defaults('locale', 'en')
            ->name('en');

        Route::get('/{page?}', PreviewPageController::class)
            ->where('page', '(?!en$)'.$pageSlug)
            ->defaults('locale', 'ar')
            ->name('ar');
    });
