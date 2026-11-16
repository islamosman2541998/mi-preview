{{-- Shared <head> for every preview template. Expects $site, $locale, $page. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="{{ \App\Support\Palette::base($site)['primary'] }}">
@php($seo = $site->seo($locale))
<title>{{ $page->is_home ? ($seo['title'] ?: $site->name($locale)) : $page->title($locale).' | '.$site->name($locale) }}</title>
@if ($seo['description'])
    <meta name="description" content="{{ $seo['description'] }}">
@endif
<link rel="alternate" hreflang="{{ $locale->other()->value }}" href="{{ $alternateUrl }}">
@if ($site->logo_nobg_path || $site->logo_path)
    <link rel="icon" href="{{ \App\Support\MediaUrl::for($site->logo_nobg_path ?: $site->logo_path) }}">
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="{{ \App\Support\Fonts::stylesheetUrl($site) }}">
{{-- Decide before first paint whether the preloader was already shown this session. --}}
<script>
    (function (d) {
        var s; try { s = window.sessionStorage; } catch (e) {}
        d.classList.add('js');
        if (!s || s.getItem('mi_pl')) d.classList.add('pl-seen');
        // safety net: never leave entrance-animated content hidden if preview.js fails
        setTimeout(function () { d.classList.add('is-ready'); }, 3000);
    })(document.documentElement);
</script>
