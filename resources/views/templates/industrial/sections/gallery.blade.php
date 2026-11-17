@php
    $images = \App\Support\SectionView::images($section);
    $spans = \App\Support\GalleryLayout::spans(count($images));
@endphp
@if (count($images))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s gallery">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null])
        {{-- each photo opens in the lightbox (a plain link to the image without JS); spans close the last row --}}
        <div class="gallery__grid" data-gallery data-reveal>
            @foreach ($images as $src)
                <a class="gallery__item {{ \App\Support\GalleryLayout::classes($spans, $loop->index, 'gallery__item--') }}" href="{{ $src }}" data-lightbox aria-label="{{ __('preview.view_photo', ['n' => $loop->iteration]) }}">
                    <img src="{{ $src }}" alt="" loading="lazy" decoding="async" width="600" height="450">
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
