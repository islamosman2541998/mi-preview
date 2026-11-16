@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s certs">
    <div class="wrap certs__grid">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])

        <ul class="certs__list">
            @foreach ($items as $item)
                <li class="cert" data-reveal style="--rd: {{ ($loop->index % 2) * 0.08 }}s">
                    <span class="cert__seal">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="" width="56" height="56" loading="lazy" decoding="async">
                        @else
                            <x-preview.icon name="award" size="28" />
                        @endif
                    </span>
                    <span class="cert__text">
                        <strong class="cert__name">{{ $item['name'] }}</strong>
                        @if (filled($item['issuer'] ?? null))<span class="cert__issuer" dir="auto">{{ $item['issuer'] }}</span>@endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
