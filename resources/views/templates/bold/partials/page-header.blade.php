{{-- Inner-page opener: a solid brand block with the title. --}}
<div class="page-head">
    <div class="wrap">
        <nav aria-label="{{ __('preview.breadcrumb') }}">
            <ol class="crumbs">
                <li><a href="{{ \App\Support\PreviewUrl::path($locale) }}">{{ __('preview.home') }}</a></li>
                <li aria-current="page">{{ $page->title($locale) }}</li>
            </ol>
        </nav>
        <h1 class="page-head__title">{{ $page->title($locale) }}</h1>
    </div>
</div>
