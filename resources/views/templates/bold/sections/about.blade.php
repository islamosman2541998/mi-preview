@php
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $points = array_values(array_filter($c['points'] ?? []));
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s about">
    <div class="wrap about__grid {{ $img ? 'about__grid--photo' : '' }}">
        <div class="about__copy">
            @if (filled($c['title'] ?? null))<h2 class="s-head__title">{{ $c['title'] }}</h2>@endif
            @if (filled($c['body'] ?? null))<p class="about__body">{{ $c['body'] }}</p>@endif
        </div>
        <div class="about__side">
            @if ($img)<img class="about__img" src="{{ $img }}" alt="{{ $c['title'] ?? '' }}" width="800" height="600" loading="lazy" decoding="async">@endif
            @if (filled($c['since'] ?? null))
                <p class="about__since"><span>{{ __('preview.facts.since') }}</span><strong>{{ $c['since'] }}</strong></p>
            @endif
            @if ($points)
                <ul class="ticks">
                    @foreach ($points as $point)<li><x-preview.icon name="check" size="18" />{{ $point }}</li>@endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
