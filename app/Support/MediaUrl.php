<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/** Section/media paths are either absolute URLs (external) or public-disk paths. */
class MediaUrl
{
    public static function for(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
