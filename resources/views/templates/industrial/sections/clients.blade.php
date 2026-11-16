@php($logos = array_values(array_filter($section->content['shared']['logos'] ?? [], fn ($l) => filled($l['name'] ?? null) || filled($l['image'] ?? null))))
@if (count($logos))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--tight clients">
    <div class="wrap clients__row">
        @if (filled($c['title'] ?? null))<h2 class="clients__title">{{ $c['title'] }}</h2>@endif
        <ul class="clients__list">
            @foreach ($logos as $logo)
                <li class="clients__item">
                    @if ($src = \App\Support\MediaUrl::for($logo['image'] ?? null))
                        <img src="{{ $src }}" alt="{{ $logo['name'] ?? '' }}" height="40" loading="lazy" decoding="async">
                    @else
                        <span class="clients__word" dir="auto">{{ $logo['name'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
