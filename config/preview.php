<?php

use App\Support\Cloudflare;

$baseDomain = env('PREVIEW_BASE_DOMAIN', 'localhost');
$adminSubdomain = env('PREVIEW_ADMIN_SUBDOMAIN', 'app');

return [

    /*
    |--------------------------------------------------------------------------
    | Domains
    |--------------------------------------------------------------------------
    |
    | {admin_subdomain}.{base_domain} serves the Filament panel, every other
    | {slug}.{base_domain} is a preview site. Locally the base is "localhost"
    | (Chrome/Edge resolve *.localhost to 127.0.0.1).
    |
    */

    'base_domain' => $baseDomain,

    'admin_subdomain' => $adminSubdomain,

    'admin_host' => $adminSubdomain.'.'.$baseDomain,

    /*
    |--------------------------------------------------------------------------
    | Brand (shown in the demo bar of every preview)
    |--------------------------------------------------------------------------
    */

    'brand_name' => env('PREVIEW_BRAND_NAME', 'MI Web Development'),

    'brand_whatsapp' => env('PREVIEW_BRAND_WHATSAPP'),

    'legal_email' => env('PREVIEW_LEGAL_EMAIL', 'privacy@mius.it.com'),

    /*
    |--------------------------------------------------------------------------
    | Seeded admin user (read via config so it survives config:cache)
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Site slugs
    |--------------------------------------------------------------------------
    |
    | Reserved slugs can never be used for preview sites: infrastructure names
    | plus every existing project subdomain on the base domain. Add new
    | project subdomains here when they are created in cPanel.
    |
    */

    'slug' => [
        'min' => 3,
        'max' => 40,
        // lowercase a-z0-9-, no leading/trailing hyphen (length checked separately)
        'pattern' => '/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/',
    ],

    'reserved_slugs' => [
        // infrastructure
        'www', 'app', 'admin', 'api', 'mail', 'webmail', 'cpanel', 'whm', 'webdisk',
        'autodiscover', 'autoconfig', 'cpcalendars', 'cpcontacts', 'ftp', 'smtp',
        'imap', 'pop', 'static', 'cdn', 'assets', 'preview', 'demo', 'test',

        // existing project subdomains (explicit DNS-only records — never touch)
        'clinic', 'funnycouples', 'mi-solutions', 'maya-portfolio', 'imposter-game',
        'lacoste', 'nabet-khalifa', 'first-trade', 'mdz-quote-system', 'ostaz-platform',
        'mechanic', 'archit', 'elle-store', 'islam-web-studio', 'bulk-sender', 'arkan',
        'abouda-elashmawy', 'topcorner', 'alfanar', 'rk-fire', 'riwaq',
    ],

    /*
    |--------------------------------------------------------------------------
    | Page slugs
    |--------------------------------------------------------------------------
    |
    | Page slugs share the URL space with the locale prefix and internal
    | endpoints, so these can never be used as a page slug.
    |
    */

    'page_slug_pattern' => '[a-z0-9]+(?:-[a-z0-9]+)*',

    'reserved_page_slugs' => ['en', 'ar', '_t', 'up', 'build', 'storage', 'js', 'css', 'favicon'],

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | Arabic is the default and is served without prefix (/ and /{page});
    | English is served under /en (/en and /en/{page}).
    |
    */

    'locales' => ['ar', 'en'],

    'default_locale' => 'ar',

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    */

    'default_template' => 'industrial',

    // Each template differs in layout and structure, not only colours (see resources/views/templates).
    'templates' => [
        'industrial' => [
            'ar' => 'صناعي / مؤسسي',
            'en' => 'Industrial / corporate',
            'font' => 'alexandria-plex',
            'for' => 'مقاولات، إطفاء، توريدات',
        ],
        'editorial' => [
            'ar' => 'مجلة / تحريري',
            'en' => 'Editorial',
            'font' => 'amiri-plex',
            'for' => 'استشارات، عقارات، مكاتب، علامات راقية',
        ],
        'boutique' => [
            'ar' => 'بوتيك',
            'en' => 'Boutique',
            'font' => 'messiri-readex',
            'for' => 'عيادات، تجميل، عطور، مطاعم',
        ],
        'bold' => [
            'ar' => 'جريء / حديث',
            'en' => 'Bold modern',
            'font' => 'kufi-plex',
            'for' => 'تجزئة، تقنية، رياضة، شركات ناشئة',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Font pairings (sites.font_key → template default when null)
    |--------------------------------------------------------------------------
    |
    | Google Fonts families (loaded with display=swap). All of them cover both
    | Arabic and Latin. Cairo/Tajawal are deliberately not offered.
    |
    */

    'fonts' => [
        'alexandria-plex' => [
            'label' => 'Alexandria + IBM Plex Sans Arabic',
            'heading' => ['family' => 'Alexandria', 'weights' => [500, 700]],
            'body' => ['family' => 'IBM Plex Sans Arabic', 'weights' => [400, 500, 600]],
        ],
        'readex' => [
            'label' => 'Readex Pro',
            'heading' => ['family' => 'Readex Pro', 'weights' => [500, 700]],
            'body' => ['family' => 'Readex Pro', 'weights' => [300, 400, 500]],
        ],
        'kufi-plex' => [
            'label' => 'Noto Kufi Arabic + IBM Plex Sans Arabic',
            'heading' => ['family' => 'Noto Kufi Arabic', 'weights' => [600, 800]],
            'body' => ['family' => 'IBM Plex Sans Arabic', 'weights' => [400, 500, 600]],
        ],
        'amiri-plex' => [
            'label' => 'Amiri + IBM Plex Sans Arabic',
            'heading' => ['family' => 'Amiri', 'weights' => [400, 700], 'fallback' => 'serif'],
            'body' => ['family' => 'IBM Plex Sans Arabic', 'weights' => [400, 500, 600]],
        ],
        'messiri-readex' => [
            'label' => 'El Messiri + Readex Pro',
            'heading' => ['family' => 'El Messiri', 'weights' => [500, 600]],
            'body' => ['family' => 'Readex Pro', 'weights' => [300, 400, 500]],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Owner exclusion cookie
    |--------------------------------------------------------------------------
    |
    | Set from the admin ("Open as owner") on the parent domain so that
    | visits from the owner's browser are never recorded.
    |
    */

    'owner_cookie' => [
        'name' => 'mi_owner',
        'domain' => env('OWNER_COOKIE_DOMAIN') ?: '.'.$baseDomain,
        'days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | View tracking
    |--------------------------------------------------------------------------
    |
    | All tracking is JS-initiated (link-preview bots don't run JS); the UA
    | filter is a second line of defence.
    |
    */

    'tracking' => [
        'ping_seconds' => 15,         // heartbeat while the tab is visible
        'stale_seconds' => 60,        // no ping for this long → visit closed + summary
        'resume_minutes' => 30,       // same visitor back within this window → same visit
        'throttle_per_minute' => 90,  // per IP, all tracking endpoints together

        'bot_pattern' => '/bot|crawl|spider|slurp|facebookexternalhit|facebookcatalog|whatsapp|telegram|'
            .'skype|slack|discord|twitter|linkedin|embedly|quora|pinterest|vkshare|w3c_validator|'
            .'headless|phantom|puppeteer|playwright|selenium|lighthouse|pagespeed|gtmetrix|'
            .'curl|wget|python|httpclient|okhttp|axios|go-http|java\/|libwww|preview|monitor|uptime/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare
    |--------------------------------------------------------------------------
    |
    | Edge ranges (list lives in App\Support\Cloudflare) — used as trusted
    | proxies and to decide whether CF-Connecting-IP can be believed.
    |
    */

    'cloudflare_ips' => Cloudflare::IPS,

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | Uploads are resized in the admin's browser before upload (no image work
    | on the shared host). Stock photos (Pixabay, Pexels) are downloaded once at a fixed width
    | so previews never hotlink and the export can zip them.
    |
    */

    'media' => [
        'max_width' => 1920,
        'stock_width' => 1920,      // Pexels; Pixabay standard keys cap at 1280 px
        'stock_per_page' => 24,
        'stock_cache_hours' => 24,  // both providers ask for cached searches
    ],

];
