{{-- Title band for inner pages (never full-height): a photo under the brand colour when the site has one. --}}
@php($banner = \App\Support\PageVisuals::banner($site, $page, $sections))
<div class="page-head {{ $banner ? 'page-head--photo' : '' }}">
    @if ($banner)
        <img class="page-head__bg" src="{{ $banner }}" alt="" width="1600" height="480" fetchpriority="high" decoding="async">
    @endif
    <div class="wrap page-head__inner">
        <h1 class="page-head__title">{{ $page->title($locale) }}</h1>
        <nav aria-label="{{ __('preview.breadcrumb') }}">
            <ol class="crumbs">
                <li><a href="{{ \App\Support\PreviewUrl::path($locale) }}">{{ __('preview.home') }}</a></li>
                <li aria-current="page">{{ $page->title($locale) }}</li>
            </ol>
        </nav>
    </div>
</div>
