{{-- Inner page opener: the title set large on paper, then a wide photograph like a spread. --}}
@php($banner = \App\Support\PageVisuals::banner($site, $page, $sections))
<div class="page-head {{ $banner ? 'page-head--photo' : '' }}">
    <div class="wrap">
        <nav aria-label="{{ __('preview.breadcrumb') }}">
            <ol class="crumbs">
                <li><a href="{{ \App\Support\PreviewUrl::path($locale) }}">{{ __('preview.home') }}</a></li>
                <li aria-current="page">{{ $page->title($locale) }}</li>
            </ol>
        </nav>
        <h1 class="page-head__title">{{ $page->title($locale) }}</h1>
    </div>
    @if ($banner)
        <div class="wrap">
            <img class="page-head__img" src="{{ $banner }}" alt="" width="1600" height="560" fetchpriority="high" decoding="async">
        </div>
    @endif
</div>
