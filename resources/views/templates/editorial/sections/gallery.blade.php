@php($images = \App\Support\SectionView::images($section))
@if (count($images))
{{-- Photos at their own proportions in staggered columns, like a picture essay; each opens in the lightbox. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s gallery">
    <div class="wrap">
        @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'index' => $index ?? null])
        <div class="essay essay--{{ min(count($images), 3) }}" data-gallery data-reveal>
            @foreach ($images as $src)
                <a class="essay__item" href="{{ $src }}" data-lightbox aria-label="{{ __('preview.view_photo', ['n' => $loop->iteration]) }}">
                    <img src="{{ $src }}" alt="" loading="lazy" decoding="async" width="600" height="750">
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
