@php
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $points = array_values(array_filter($c['points'] ?? []));
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s about {{ $img ? 'about--photo' : 'about--text' }}">
    <div class="wrap about__grid">
        @if ($img)
            <figure class="about__figure">
                <img src="{{ $img }}" alt="{{ $c['title'] ?? '' }}" width="960" height="720" loading="lazy" decoding="async">
                @if (filled($c['since'] ?? null))<figcaption>{{ __('preview.facts.since') }} {{ $c['since'] }}</figcaption>@endif
            </figure>
        @endif
        <div class="about__copy">
            @if (filled($c['title'] ?? null))<h2 class="s-head__title">{{ $c['title'] }}</h2>@endif
            @if (filled($c['body'] ?? null))<p class="about__body">{{ $c['body'] }}</p>@endif
            @if ($points)
                <ul class="about__points">
                    @foreach ($points as $point)<li>{{ $point }}</li>@endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
