@php
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $secondary = \App\Support\PreviewUrl::localize($c['secondary_target'] ?? null, $locale);
    $Heading = $page->is_home ? 'h1' : 'h2';
    $hours = \App\Support\PageVisuals::hours($site, $locale);
@endphp
{{-- Copy beside a large arched photograph; a small card on the arch gives the phone and opening hours. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="hero {{ $img ? 'hero--photo' : 'hero--plain' }}">
    <div class="wrap hero__grid">
        <div class="hero__copy" data-reveal>
            @if (filled($c['eyebrow'] ?? null))<p class="kicker">{{ $c['eyebrow'] }}</p>@endif
            <{{ $Heading }} class="hero__title">{{ $c['title'] ?? $site->name($locale) }}</{{ $Heading }}>
            @if (filled($c['subtitle'] ?? null))<p class="hero__lead">{{ $c['subtitle'] }}</p>@endif
            <div class="hero__actions">
                @if ($wa && filled($c['primary_cta'] ?? null))
                    <a class="pill pill--accent pill--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="hero">
                        <x-preview.icon name="whatsapp" size="20" />{{ $c['primary_cta'] }}
                    </a>
                @elseif ($site->phoneLink() && filled($c['primary_cta'] ?? null))
                    <a class="pill pill--accent pill--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="hero">
                        <x-preview.icon name="phone" size="20" />{{ $c['primary_cta'] }}
                    </a>
                @endif
                @if ($secondary && filled($c['secondary_cta'] ?? null))
                    <a class="pill pill--soft pill--lg" href="{{ $secondary }}">{{ $c['secondary_cta'] }}</a>
                @endif
            </div>
        </div>

        <div class="hero__media" data-reveal>
            @include('templates.boutique.partials.arch', ['img' => $img, 'class' => 'hero__arch', 'eager' => true])
            @if ($site->phone || $hours)
                <div class="hero__card">
                    @if ($hours)
                        <p class="hero__card-row"><x-preview.icon name="clock" size="18" /><span>{{ $hours }}</span></p>
                    @endif
                    @if ($site->phone)
                        <a class="hero__card-row" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="hero_card" dir="ltr"><x-preview.icon name="phone" size="18" /><span>{{ $site->phone }}</span></a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
