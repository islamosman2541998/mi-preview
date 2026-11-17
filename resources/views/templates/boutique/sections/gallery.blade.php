@php
    $images = \App\Support\SectionView::images($section);
    $spans = \App\Support\GalleryLayout::spans(count($images));
@endphp
@if (count($images))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s gallery">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="mosaic" data-gallery data-reveal>
            @foreach ($images as $src)
                <a class="mosaic__item {{ \App\Support\GalleryLayout::classes($spans, $loop->index, 'mo--') }}" href="{{ $src }}" data-lightbox aria-label="{{ __('preview.view_photo', ['n' => $loop->iteration]) }}">
                    <img src="{{ $src }}" alt="" loading="lazy" decoding="async" width="600" height="600">
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
