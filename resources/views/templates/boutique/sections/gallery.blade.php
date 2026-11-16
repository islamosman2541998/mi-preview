@php($images = \App\Support\SectionView::images($section))
@if (count($images))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s gallery">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="mosaic">
            @foreach ($images as $src)
                <figure class="mosaic__item"><img src="{{ $src }}" alt="" width="700" height="800" loading="lazy" decoding="async"></figure>
            @endforeach
        </div>
    </div>
</section>
@endif
