@php($images = \App\Support\SectionView::images($section))
@if (count($images))
{{-- A picture spread: uneven crops, the first one dominant. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s gallery">
    <div class="wrap">
        @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="spread">
            @foreach ($images as $src)
                <figure class="spread__item"><img src="{{ $src }}" alt="" width="900" height="700" loading="lazy" decoding="async"></figure>
            @endforeach
        </div>
    </div>
</section>
@endif
