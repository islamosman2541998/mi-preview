@php($items = \App\Support\SectionView::items($section, $c, 'items', 'q'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s faq">
    <div class="wrap faq__inner">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <div class="faq__list">
            @foreach ($items as $item)
                <details class="faq__item">
                    <summary>{{ $item['q'] }}<span class="faq__sign" aria-hidden="true"><x-preview.icon name="plus" size="16" /></span></summary>
                    <p>{{ $item['a'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
