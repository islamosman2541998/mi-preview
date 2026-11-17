@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s certs">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="certs__list">
            @foreach ($items as $item)
                <li class="cert" data-reveal>
                    <span class="cert__seal">
                        @if ($item['img'])<img src="{{ $item['img'] }}" alt="" width="64" height="64" loading="lazy" decoding="async">@else<x-preview.icon name="award" size="28" />@endif
                    </span>
                    <strong class="cert__name">{{ $item['name'] }}</strong>
                    @if (filled($item['issuer'] ?? null))<span class="cert__issuer" dir="auto">{{ $item['issuer'] }}</span>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
