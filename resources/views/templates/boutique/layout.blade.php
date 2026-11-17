{{--
    Boutique template — clinics, beauty, perfume, restaurants.
    Warm and soft: arched photographs, tinted bands, rounded panels, horizontal
    rails for work and reviews, booking given real prominence.
    Colours come only from the CSS custom properties set on <html>.
--}}
<!DOCTYPE html>
<html lang="{{ $locale->value }}" dir="{{ $locale->dir() }}"
      style="{{ \App\Support\Palette::inlineCss($site) }};{{ \App\Support\Fonts::cssVariables($site) }}">
<head>
    @include('preview.partials.head')
    @vite(['resources/css/templates/boutique.css', 'resources/js/preview.js'])
    @stack('head')
</head>
<body class="tpl-boutique">
<a class="skip-link" href="#main">{{ __('preview.skip') }}</a>

@include('preview.partials.preloader')
@include('templates.boutique.partials.nav')

<main id="main" tabindex="-1">
    @if (! $page->is_home && $sections->first()?->type !== \App\Enums\SectionType::Hero)
        @include('templates.boutique.partials.page-header')
    @endif

    @foreach ($sections as $section)
        @include('templates.boutique.sections.'.$section->type->value, [
            'section' => $section,
            'c' => $section->contentFor($locale),
            'anchor' => $section->anchor(),
        ])
    @endforeach
</main>

@include('templates.boutique.partials.footer')
@include('preview.partials.whatsapp-float')
@include('preview.partials.tracking')
@stack('body')
</body>
</html>
