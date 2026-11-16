@php($logos = \App\Support\SectionView::logos($section))
@if (count($logos))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--tight clients">
    <div class="wrap">
        @if (filled($c['title'] ?? null))<h2 class="clients__title">{{ $c['title'] }}</h2>@endif
        <ul class="chips chips--center">
            @foreach ($logos as $logo)
                <li class="chip chip--plain">
                    @if ($logo['img'])
                        <img src="{{ $logo['img'] }}" alt="{{ $logo['name'] ?? '' }}" height="28" loading="lazy" decoding="async">
                    @else
                        <span dir="auto">{{ $logo['name'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
