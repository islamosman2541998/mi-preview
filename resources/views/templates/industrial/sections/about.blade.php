@php($img = \App\Support\MediaUrl::for($c['image'] ?? null))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s about {{ $img ? 'about--media' : 'about--text' }}">
    <div class="wrap about__grid">
        <div class="about__copy">
            @if (filled($c['title'] ?? null))<h2 class="s-head__title">{{ $c['title'] }}</h2>@endif
            @if (filled($c['body'] ?? null))<p class="about__body">{{ $c['body'] }}</p>@endif
        </div>

        @if (! empty($c['points']))
            <ul class="checklist about__points">
                @foreach ($c['points'] as $point)
                    @continue(blank($point))
                    <li><x-preview.icon name="check" size="18" />{{ $point }}</li>
                @endforeach
            </ul>
        @endif

        @if ($img)
            <figure class="about__media">
                <img src="{{ $img }}" alt="{{ $c['title'] ?? '' }}" width="720" height="540" loading="lazy" decoding="async">
            </figure>
        @endif
    </div>
</section>
