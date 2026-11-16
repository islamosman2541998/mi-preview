<?php

namespace App\Support;

use App\Enums\Locale;

/**
 * Builds preview-site URLs: {scheme}://{slug}.{base}[:port]/[en/][page].
 * Scheme and port follow APP_URL so the same code works on
 * http://*.localhost:8000 and https://*.mius.it.com.
 */
class PreviewUrl
{
    public static function host(string $slug): string
    {
        return $slug.'.'.config('preview.base_domain');
    }

    public static function root(string $slug): string
    {
        $app = parse_url((string) config('app.url'));
        $scheme = $app['scheme'] ?? 'https';
        $port = isset($app['port']) ? ':'.$app['port'] : '';

        return $scheme.'://'.static::host($slug).$port;
    }

    /** Relative path inside a preview site, e.g. "/", "/about", "/en", "/en/about". */
    public static function path(?Locale $locale = null, ?string $pageSlug = null): string
    {
        $locale ??= Locale::default();
        $path = $locale->prefix().($pageSlug ? '/'.$pageSlug : '');

        return $path === '' ? '/' : $path;
    }

    public static function to(string $slug, ?Locale $locale = null, ?string $pageSlug = null): string
    {
        return static::root($slug).static::path($locale, $pageSlug);
    }

    /**
     * Content link targets → href for the current locale:
     * "#services" stays, "/projects" → "/en/projects", absolute URLs stay.
     */
    public static function localize(?string $target, ?Locale $locale = null): ?string
    {
        if (blank($target)) {
            return null;
        }

        if (str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            $page = trim($target, '/');

            return static::path($locale, $page === '' ? null : $page);
        }

        return $target;
    }
}
