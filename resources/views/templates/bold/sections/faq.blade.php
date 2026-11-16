@php($items = \App\Support\SectionView::items($section, $c, 'items', 'q'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s faq">
    <div class="wrap faq__grid">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <div class="faq__list">
            @foreach ($items as $item)
                <details class="faq__item">
                    <summary><span>{{ $item['q'] }}</span><span class="faq__box" aria-hidden="true"><x-preview.icon name="plus" size="18" /></span></summary>
                    <p>{{ $item['a'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
