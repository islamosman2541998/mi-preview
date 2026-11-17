{{-- Inner page opener: a brand-colour block with the title, a photo panel beside it when the site has one. --}}
@php($banner = \App\Support\PageVisuals::banner($site, $page, $sections))
<div class="page-head {{ $banner ? 'page-head--photo' : '' }}">
    <div class="page-head__block">
        <nav aria-label="{{ __('preview.breadcrumb') }}">
            <ol class="crumbs">
                <li><a href="{{ \App\Support\PreviewUrl::path($locale) }}">{{ __('preview.home') }}</a></li>
                <li aria-current="page">{{ $page->title($locale) }}</li>
            </ol>
        </nav>
        <h1 class="page-head__title">{{ $page->title($locale) }}</h1>
    </div>
    @if ($banner)
        <img class="page-head__img" src="{{ $banner }}" alt="" width="1000" height="460" fetchpriority="high" decoding="async">
    @endif
</div>
