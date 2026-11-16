@php($logos = \App\Support\SectionView::logos($section))
@if (count($logos))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="clients">
    <div class="wrap">
        @if (filled($c['title'] ?? null))<h2 class="clients__title">{{ $c['title'] }}</h2>@endif
        <ul class="clients__grid" data-reveal>
            @foreach ($logos as $logo)
                <li class="client">
                    @if ($logo['img'])
                        <img src="{{ $logo['img'] }}" alt="{{ $logo['name'] ?? '' }}" height="44" loading="lazy" decoding="async">
                    @else
                        {{-- no logo file: a monogram tile, not a blank box --}}
                        <span class="client__mono" aria-hidden="true">{{ \App\Support\SectionView::initial($logo['name']) }}</span>
                        <span class="client__name" dir="auto">{{ $logo['name'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
