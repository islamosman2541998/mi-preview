@php
    $images = \App\Support\SectionView::images($section);
    $spans = \App\Support\GalleryLayout::spans(count($images));
@endphp
@if (count($images))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s gallery">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null])
    </div>
    {{-- a tight square grid, edge to edge --}}
    <div class="squares" data-gallery data-reveal>
        @foreach ($images as $src)
            <a class="squares__item {{ \App\Support\GalleryLayout::classes($spans, $loop->index, 'sq--') }}" href="{{ $src }}" data-lightbox aria-label="{{ __('preview.view_photo', ['n' => $loop->iteration]) }}">
                <img src="{{ $src }}" alt="" loading="lazy" decoding="async" width="480" height="480">
            </a>
        @endforeach
    </div>
</section>
@endif
