{{-- Inner page opener: the title over a rounded photograph inside the page, or on a soft band without one. --}}
@php($banner = \App\Support\PageVisuals::banner($site, $page, $sections))
<div class="page-head {{ $banner ? 'page-head--photo' : '' }}">
    <div class="wrap">
        <div class="page-head__frame">
            @if ($banner)
                <img class="page-head__img" src="{{ $banner }}" alt="" width="1600" height="560" fetchpriority="high" decoding="async">
            @endif
            <div class="page-head__text">
                <h1 class="page-head__title">{{ $page->title($locale) }}</h1>
                <nav aria-label="{{ __('preview.breadcrumb') }}">
                    <ol class="crumbs">
                        <li><a href="{{ \App\Support\PreviewUrl::path($locale) }}">{{ __('preview.home') }}</a></li>
                        <li aria-current="page">{{ $page->title($locale) }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
