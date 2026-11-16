<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Models\Site;
use App\Rules\SiteSlug;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * {slug}.{base} → loads the (non-archived) Site, sets the locale from the
 * route ("/en/…" → en, otherwise ar) and shares both with the views.
 * The {subdomain} and {locale} route parameters are consumed here so
 * controllers only receive the page slug.
 */
class ResolvePreviewSite
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $slug = strtolower((string) $route->parameter('subdomain'));
        $locale = Locale::tryFrom((string) $route->parameter('locale')) ?? Locale::default();

        $route->forgetParameter('subdomain');
        $route->forgetParameter('locale');

        abort_unless(SiteSlug::passes($slug), 404);

        $site = Site::query()->servable()->where('slug', $slug)->first();

        abort_if($site === null, 404);

        app()->setLocale($locale->value);
        app()->instance(Site::class, $site);
        app()->instance(Locale::class, $locale);
        $request->attributes->set('site', $site);

        view()->share([
            'site' => $site,
            'locale' => $locale,
        ]);

        return $next($request);
    }
}
