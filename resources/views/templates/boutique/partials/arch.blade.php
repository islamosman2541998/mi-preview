{{-- The boutique signature: a photo in an arch, or the brand colour with the name's initial. --}}
<figure class="arch {{ $img ? '' : 'arch--empty' }} {{ $class ?? '' }}">
    @if ($img)
        <img src="{{ $img }}" alt="{{ $alt ?? '' }}" width="{{ $w ?? 800 }}" height="{{ $h ?? 1000 }}"
             @if ($eager ?? false) fetchpriority="high" @else loading="lazy" @endif decoding="async">
    @else
        <span aria-hidden="true">{{ \App\Support\SectionView::initial($site->name($locale)) }}</span>
    @endif
</figure>
