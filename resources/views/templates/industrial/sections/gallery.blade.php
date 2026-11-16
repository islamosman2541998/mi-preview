@php
    $images = \App\Support\SectionView::images($section);
    $count = count($images);
    // First photo large (2×2) from five photos up; the last tiles widen so no row ends with a hole.
    $feature = $count >= 5;
    $rest = $feature ? $count - 1 : $count;
    $tail = $feature ? $rest % 4 : 0; // desktop grid has 4 columns
    $spans = function (int $index) use ($feature, $rest, $tail, $count): string {
        $i = $feature ? $index - 1 : $index;   // position among the regular tiles
        $fromEnd = $rest - 1 - $i;
        $classes = [];
        if ($feature && $i >= 0) {
            if ($tail === 1 && $fromEnd === 0) $classes[] = 'gallery__item--d4';
            if ($tail === 2 && $fromEnd <= 1) $classes[] = 'gallery__item--d2';
            if ($tail === 3 && $fromEnd === 0) $classes[] = 'gallery__item--d2';
        }
        if ($i >= 0 && $rest % 2 === 1 && $fromEnd === 0) $classes[] = 'gallery__item--m2'; // phones: 2 columns
        return implode(' ', $classes);
    };
@endphp
@if ($count)
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s gallery">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null])
        {{-- each photo opens in the lightbox (a plain link to the image without JS) --}}
        <div class="gallery__grid {{ $feature ? 'gallery__grid--feature' : 'gallery__grid--n'.$count }}" data-gallery data-reveal>
            @foreach ($images as $src)
                <a class="gallery__item {{ $spans($loop->index) }}" href="{{ $src }}" data-lightbox aria-label="{{ __('preview.view_photo', ['n' => $loop->iteration]) }}">
                    <img src="{{ $src }}" alt="" loading="lazy" decoding="async" width="600" height="450">
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
