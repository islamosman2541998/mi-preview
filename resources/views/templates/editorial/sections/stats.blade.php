@php($items = \App\Support\SectionView::items($section, $c, 'items', 'label'))
@if (count($items))
{{-- Figures set as display numerals, like the data box in a feature. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s figures">
    <div class="wrap">
        @if (filled($c['title'] ?? null))<h2 class="figures__title">{{ $c['title'] }}</h2>@endif
        <dl class="figures__list">
            @foreach ($items as $item)
                <div class="figures__item">
                    <dt class="figures__label">{{ $item['label'] }}</dt>
                    <dd class="figures__value">{{ \App\Support\SectionView::number($item['value'] ?? null) }}<span>{{ $item['suffix'] ?? '' }}</span></dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
@endif
