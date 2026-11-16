{{--
    Industrial / corporate template — contracting, fire protection, supply.
    Dense information, a signboard-style hero, dark bands for figures and CTA.
    Colours come only from the CSS custom properties set on <html>.
--}}
<!DOCTYPE html>
<html lang="{{ $locale->value }}" dir="{{ $locale->dir() }}"
      style="{{ \App\Support\Palette::inlineCss($site) }};{{ \App\Support\Fonts::cssVariables($site) }}">
<head>
    @include('preview.partials.head')
    @vite(['resources/css/templates/industrial.css', 'resources/js/preview.js'])
    @stack('head')
</head>
<body class="tpl-industrial">
<a class="skip-link" href="#main">{{ __('preview.skip') }}</a>

@include('preview.partials.preloader')
@include('templates.industrial.partials.nav')

<main id="main" tabindex="-1">
    @if (! $page->is_home && $sections->first()?->type !== \App\Enums\SectionType::Hero)
        @include('templates.industrial.partials.page-header')
    @endif

    @foreach ($sections as $section)
        @include('templates.industrial.sections.'.$section->type->value, [
            'section' => $section,
            'c' => $section->contentFor($locale),
            'anchor' => $section->anchor(),
        ])
    @endforeach
</main>

@include('templates.industrial.partials.footer')
@include('preview.partials.whatsapp-float')
@include('preview.partials.tracking')
@stack('body')
</body>
</html>
