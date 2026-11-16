@php
    $labels = $c['items'] ?? [];
    $values = $section->content['shared']['items'] ?? [];
@endphp
@if (count($labels))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--deep figures">
    <div class="wrap">
        @if (filled($c['title'] ?? null))<h2 class="figures__title">{{ $c['title'] }}</h2>@endif
        <dl class="figures__list">
            @foreach ($labels as $i => $item)
                @php($v = $values[$i] ?? [])
                <div class="figures__item">
                    <dt class="figures__label">{{ $item['label'] ?? '' }}</dt>
                    <dd class="figures__value">{{ is_numeric($v['value'] ?? null) ? number_format((float) $v['value']) : ($v['value'] ?? '') }}<span class="figures__suffix">{{ $v['suffix'] ?? '' }}</span></dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
@endif
