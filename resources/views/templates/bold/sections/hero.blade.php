@php
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $secondary = \App\Support\PreviewUrl::localize($c['secondary_target'] ?? null, $locale);
    $Heading = $page->is_home ? 'h1' : 'h2';
@endphp
{{-- Two solid blocks side by side: the brand colour with the headline, the photo beside it. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="hero {{ $img ? 'hero--photo' : 'hero--solid' }}">
    <div class="hero__block">
        <div class="hero__copy">
            @if (filled($c['eyebrow'] ?? null))<p class="tag">{{ $c['eyebrow'] }}</p>@endif
            <{{ $Heading }} class="hero__title">{{ $c['title'] ?? $site->name($locale) }}</{{ $Heading }}>
            @if (filled($c['subtitle'] ?? null))<p class="hero__lead">{{ $c['subtitle'] }}</p>@endif
            <div class="hero__actions">
                @if ($wa && filled($c['primary_cta'] ?? null))
                    <a class="block-btn block-btn--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="hero">
                        <x-preview.icon name="whatsapp" size="20" />{{ $c['primary_cta'] }}
                    </a>
                @elseif ($site->phoneLink() && filled($c['primary_cta'] ?? null))
                    <a class="block-btn block-btn--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="hero">
                        <x-preview.icon name="phone" size="20" />{{ $c['primary_cta'] }}
                    </a>
                @endif
                @if ($secondary && filled($c['secondary_cta'] ?? null))
                    <a class="block-btn block-btn--outline block-btn--lg" href="{{ $secondary }}">{{ $c['secondary_cta'] }}</a>
                @endif
            </div>
        </div>
    </div>
    @if ($img)
        <div class="hero__media"><img src="{{ $img }}" alt="" width="1000" height="800" fetchpriority="high" decoding="async"></div>
    @endif
</section>
