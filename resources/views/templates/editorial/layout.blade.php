{{--
    Editorial template — consultancies, real estate, offices, premium brands.
    A magazine feature: a serif display face carries the page, layouts are
    asymmetric (5/7 splits), photos are cropped like spreads and captioned.
    Colours come only from the CSS custom properties set on <html>.
--}}
<!DOCTYPE html>
<html lang="{{ $locale->value }}" dir="{{ $locale->dir() }}"
      style="{{ \App\Support\Palette::inlineCss($site) }};{{ \App\Support\Fonts::cssVariables($site) }}">
<head>
    @include('preview.partials.head')
    @vite(['resources/css/templates/editorial.css', 'resources/js/preview.js'])
    @stack('head')
</head>
<body class="tpl-editorial">
<a class="skip-link" href="#main">{{ __('preview.skip') }}</a>

@include('preview.partials.preloader')
@include('preview.partials.demo-bar')
@include('templates.editorial.partials.nav')

<main id="main" tabindex="-1">
    @if (! $page->is_home && $sections->first()?->type !== \App\Enums\SectionType::Hero)
        @include('templates.editorial.partials.page-header')
    @endif

    @foreach ($sections as $section)
        @include('templates.editorial.sections.'.$section->type->value, [
            'section' => $section,
            'c' => $section->contentFor($locale),
            'anchor' => $section->anchor(),
        ])
    @endforeach
</main>

@include('templates.editorial.partials.footer')
@include('preview.partials.whatsapp-float')
@include('preview.partials.tracking')
@stack('body')
</body>
</html>
