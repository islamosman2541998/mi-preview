@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'label');
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
@endphp
@if (count($items))
{{-- Solid blocks in the brand colours, numbers set huge; a photo block leads when there is one. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="figures {{ $img ? 'figures--photo' : '' }}">
    @if (filled($c['title'] ?? null))<div class="wrap"><h2 class="figures__title" data-reveal>{{ $c['title'] }}</h2></div>@endif
    <div class="figures__grid">
        @if ($img)
            <img class="figures__img" src="{{ $img }}" alt="" width="900" height="600" loading="lazy" decoding="async">
        @endif
        <dl class="figures__list figures__list--{{ min(count($items), 4) }}" data-reveal>
            @foreach ($items as $item)
                @php($value = $item['value'] ?? null)
                <div class="figures__item">
                    <dt class="figures__label">{{ $item['label'] }}</dt>
                    <dd class="figures__value">
                        @if (is_numeric($value))<span data-count="{{ $value + 0 }}">{{ \App\Support\SectionView::number($value) }}</span>@else<span>{{ $value }}</span>@endif
                        @if (filled($item['suffix'] ?? null))<span class="figures__suffix">{{ $item['suffix'] }}</span>@endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
@endif
