@php
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $secondary = \App\Support\PreviewUrl::localize($c['secondary_target'] ?? null, $locale);
    $Heading = $page->is_home ? 'h1' : 'h2';
    $caption = $site->address($locale) ?: $site->industry?->name($locale);
@endphp
{{-- A feature opener: oversized serif headline beside a tall, captioned photograph. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="hero {{ $img ? 'hero--photo' : 'hero--type' }}">
    <div class="wrap hero__grid">
        <div class="hero__copy">
            @if (filled($c['eyebrow'] ?? null))<p class="kicker">{{ $c['eyebrow'] }}</p>@endif
            <{{ $Heading }} class="hero__title">{{ $c['title'] ?? $site->name($locale) }}</{{ $Heading }}>
            @if (filled($c['subtitle'] ?? null))<p class="hero__lead">{{ $c['subtitle'] }}</p>@endif
            <div class="hero__actions">
                @if ($wa && filled($c['primary_cta'] ?? null))
                    <a class="btn" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="hero">{{ $c['primary_cta'] }}</a>
                @elseif ($site->phoneLink() && filled($c['primary_cta'] ?? null))
                    <a class="btn" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="hero">{{ $c['primary_cta'] }}</a>
                @endif
                @if ($secondary && filled($c['secondary_cta'] ?? null))
                    <a class="text-link" href="{{ $secondary }}">{{ $c['secondary_cta'] }}</a>
                @endif
            </div>
        </div>

        @if ($img)
            <figure class="hero__figure">
                <img src="{{ $img }}" alt="" width="900" height="1125" fetchpriority="high" decoding="async">
                @if ($caption)<figcaption>{{ $caption }}</figcaption>@endif
            </figure>
        @endif
    </div>
</section>
