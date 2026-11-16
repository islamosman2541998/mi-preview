@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'label');
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="figures {{ $img ? 'figures--photo' : '' }}">
    @if ($img)
        <img class="figures__bg" src="{{ $img }}" alt="" width="1600" height="600" loading="lazy" decoding="async">
    @endif
    <div class="wrap">
        @if (filled($c['title'] ?? null))<h2 class="figures__title" data-reveal>{{ $c['title'] }}</h2>@endif
        <dl class="figures__list" data-reveal>
            @foreach ($items as $item)
                @php($value = $item['value'] ?? null)
                <div class="figures__item">
                    <dt class="figures__label">{{ $item['label'] }}</dt>
                    <dd class="figures__value">
                        @if (is_numeric($value))
                            <span data-count="{{ $value + 0 }}">{{ \App\Support\SectionView::number($value) }}</span>
                        @else
                            <span>{{ $value }}</span>
                        @endif
                        @if (filled($item['suffix'] ?? null))<span class="figures__suffix">{{ $item['suffix'] }}</span>@endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
@endif
