{{--
    Temporary renderer used until resources/views/templates/{key}/layout.blade.php
    exists for the site's template. Shows the resolved data so routing,
    locale and content can be verified.
--}}
<!DOCTYPE html>
<html lang="{{ $locale->value }}" dir="{{ $locale->dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $page->title($locale) }} — {{ $site->name($locale) }}</title>
    <style>
        :root {
            --c-primary: {{ $site->color('primary') ?? '#1f2937' }};
            --c-text: {{ $site->color('text') ?? '#111827' }};
            --c-bg: {{ $site->color('bg') ?? '#ffffff' }};
        }
        body { margin: 0; font: 16px/1.6 system-ui, sans-serif; color: var(--c-text); background: var(--c-bg); }
        header, main { max-width: 960px; margin: 0 auto; padding: 16px; }
        header { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
        nav a { margin-inline-end: 12px; color: var(--c-primary); }
        section { padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
        small { color: #6b7280; }
    </style>
</head>
<body>
<header>
    <strong>{{ $site->name($locale) }}</strong>
    <nav>
        @foreach ($site->menu as $item)
            <a href="{{ $item->href($site, $locale, $homeSlug) }}">{{ $item->label($locale) }}</a>
        @endforeach
        <a href="{{ $alternateUrl }}">{{ $locale->other()->getLabel() }}</a>
    </nav>
</header>
<main>
    <h1>{{ $page->title($locale) }}</h1>
    <p><small>Template "{{ $site->template }}" not built yet — placeholder renderer.</small></p>

    @foreach ($sections as $section)
        @php($c = $section->contentFor($locale))
        <section id="{{ $section->anchor() }}" data-section="{{ $section->anchor() }}">
            <small>{{ $section->type->value }}{{ $section->variant ? ' · '.$section->variant : '' }}</small>
            <h2>{{ $c['title'] ?? $section->type->getLabel() }}</h2>
            @isset($c['subtitle'])<p>{{ $c['subtitle'] }}</p>@endisset
            @isset($c['body'])<p>{{ $c['body'] }}</p>@endisset
        </section>
    @endforeach
</main>
</body>
</html>
