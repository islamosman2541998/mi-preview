@php($images = \App\Support\SectionView::images($section))
@if (count($images))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s gallery">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null])
    </div>
    <div class="wall">
        @foreach ($images as $src)
            <figure class="wall__item"><img src="{{ $src }}" alt="" width="700" height="700" loading="lazy" decoding="async"></figure>
        @endforeach
    </div>
</section>
@endif
