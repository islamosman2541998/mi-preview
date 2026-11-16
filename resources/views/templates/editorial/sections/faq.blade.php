@php($items = \App\Support\SectionView::items($section, $c, 'items', 'q'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s faq">
    <div class="wrap split">
        <div class="split__side">
            @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        </div>
        <div class="split__main">
            @foreach ($items as $item)
                <details class="qa">
                    <summary>{{ $item['q'] }}<x-preview.icon name="plus" size="18" /></summary>
                    <p>{{ $item['a'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
