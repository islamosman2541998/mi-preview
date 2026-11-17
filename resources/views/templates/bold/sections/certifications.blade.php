@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s certs">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="certs__grid" data-reveal>
            @foreach ($items as $item)
                <li class="cert">
                    <span class="cert__seal">@if ($item['img'])<img src="{{ $item['img'] }}" alt="" width="48" height="48" loading="lazy" decoding="async">@else<x-preview.icon name="award" size="26" />@endif</span>
                    <strong class="cert__name">{{ $item['name'] }}</strong>
                    @if (filled($item['issuer'] ?? null))<span class="cert__issuer" dir="auto">{{ $item['issuer'] }}</span>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
