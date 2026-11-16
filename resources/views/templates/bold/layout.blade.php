{{--
    Bold modern template — retail, tech, sports, young brands.
    High contrast and compact: solid brand-colour blocks, a heavy Kufi display
    face, square corners, tight grids where content sits edge to edge.
    Colours come only from the CSS custom properties set on <html>.
--}}
<!DOCTYPE html>
<html lang="{{ $locale->value }}" dir="{{ $locale->dir() }}"
      style="{{ \App\Support\Palette::inlineCss($site) }};{{ \App\Support\Fonts::cssVariables($site) }}">
<head>
    @include('preview.partials.head')
    @vite(['resources/css/templates/bold.css', 'resources/js/preview.js'])
    @stack('head')
</head>
<body class="tpl-bold">
<a class="skip-link" href="#main">{{ __('preview.skip') }}</a>

@include('preview.partials.preloader')
@include('templates.bold.partials.nav')

<main id="main" tabindex="-1">
    @if (! $page->is_home && $sections->first()?->type !== \App\Enums\SectionType::Hero)
        @include('templates.bold.partials.page-header')
    @endif

    @foreach ($sections as $section)
        @include('templates.bold.sections.'.$section->type->value, [
            'section' => $section,
            'c' => $section->contentFor($locale),
            'anchor' => $section->anchor(),
        ])
    @endforeach
</main>

@include('templates.bold.partials.footer')
@include('preview.partials.whatsapp-float')
@include('preview.partials.tracking')
@stack('body')
</body>
</html>
