<?php

use App\Http\Controllers\Admin\MediaPickerController;
use App\Http\Controllers\Admin\OpenAsOwnerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin host — {admin}.{base}
|--------------------------------------------------------------------------
|
| The Filament panel registers its own routes on this domain (see
| AdminPanelProvider). Admin-only helper routes go in this group. Any host
| that is neither the admin host nor a preview site matches no route and
| gets the neutral 404 page.
|
*/

Route::domain(config('preview.admin_host'))
    ->name('legal.')
    ->group(function () {
        Route::view('/privacy-policy', 'legal.privacy')->name('privacy');
        Route::view('/terms', 'legal.terms')->name('terms');
        Route::view('/data-deletion', 'legal.data-deletion')->name('data-deletion');
    });

Route::domain(config('preview.admin_host'))
    ->middleware('auth')
    ->name('admin.')
    ->group(function () {
        Route::get('/owner/{site}', OpenAsOwnerController::class)->name('open-as-owner');

        // JSON for the image picker (stock photo search + the site's own library)
        Route::prefix('_picker')->name('picker.')->group(function () {
            Route::get('/stock/{provider}', [MediaPickerController::class, 'stock'])
                ->whereIn('provider', ['pixabay', 'pexels'])
                ->middleware('throttle:60,1')
                ->name('stock');
            Route::get('/sites/{site}/media', [MediaPickerController::class, 'library'])->name('library');
        });
    });
