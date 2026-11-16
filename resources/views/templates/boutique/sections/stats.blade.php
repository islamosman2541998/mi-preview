@php($items = \App\Support\SectionView::items($section, $c, 'items', 'label'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--tight figures">
    <div class="wrap">
        <div class="figures__panel">
            @if (filled($c['title'] ?? null))<h2 class="figures__title">{{ $c['title'] }}</h2>@endif
            <dl class="figures__list">
                @foreach ($items as $item)
                    <div class="figures__item">
                        <dt>{{ $item['label'] }}</dt>
                        <dd>{{ \App\Support\SectionView::number($item['value'] ?? null) }}{{ $item['suffix'] ?? '' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</section>
@endif
