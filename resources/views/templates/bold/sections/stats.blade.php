@php($items = \App\Support\SectionView::items($section, $c, 'items', 'label'))
@if (count($items))
{{-- A solid accent block with oversized numbers. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="numbers">
    <div class="wrap">
        @if (filled($c['title'] ?? null))<h2 class="numbers__title">{{ $c['title'] }}</h2>@endif
        <dl class="numbers__list">
            @foreach ($items as $item)
                <div class="numbers__item">
                    <dt>{{ $item['label'] }}</dt>
                    <dd>{{ \App\Support\SectionView::number($item['value'] ?? null) }}{{ $item['suffix'] ?? '' }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
@endif
