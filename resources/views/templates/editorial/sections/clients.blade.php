@php($logos = \App\Support\SectionView::logos($section))
@if (count($logos))
{{-- Clients typeset as a list in columns; logos, when there are some, in quiet grey. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--tight clients">
    <div class="wrap clients__grid">
        @if (filled($c['title'] ?? null))<h2 class="clients__title" data-reveal>{{ $c['title'] }}</h2>@endif
        <ul class="clients__list" data-reveal>
            @foreach ($logos as $logo)
                <li class="clients__item">
                    @if ($logo['img'])
                        <img src="{{ $logo['img'] }}" alt="{{ $logo['name'] ?? '' }}" height="40" loading="lazy" decoding="async">
                    @else
                        <span dir="auto">{{ $logo['name'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
