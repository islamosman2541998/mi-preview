@php
    $facts = \App\Support\SiteFacts::for($site, $locale, $c);
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $secondary = \App\Support\PreviewUrl::localize($c['secondary_target'] ?? null, $locale);
    $Heading = $page->is_home ? 'h1' : 'h2';
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="hero {{ $img ? 'hero--photo' : 'hero--solid' }}">
    @if ($img)
        <img class="hero__bg" src="{{ $img }}" alt="" width="1600" height="900" fetchpriority="high" decoding="async">
    @endif
    <div class="wrap hero__grid">
        <div class="hero__copy">
            @if (filled($c['eyebrow'] ?? null))
                <p class="hero__kicker">{{ $c['eyebrow'] }}</p>
            @endif
            <{{ $Heading }} class="hero__title">{{ $c['title'] ?? $site->name($locale) }}</{{ $Heading }}>
            @if (filled($c['subtitle'] ?? null))
                <p class="hero__lead">{{ $c['subtitle'] }}</p>
            @endif
            <div class="hero__actions">
                @if ($wa && filled($c['primary_cta'] ?? null))
                    <a class="btn btn--signal btn--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="hero">
                        <x-preview.icon name="whatsapp" size="20" />{{ $c['primary_cta'] }}
                    </a>
                @elseif ($site->phoneLink() && filled($c['primary_cta'] ?? null))
                    <a class="btn btn--signal btn--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="hero">
                        <x-preview.icon name="phone" size="20" />{{ $c['primary_cta'] }}
                    </a>
                @endif
                @if ($secondary && filled($c['secondary_cta'] ?? null))
                    <a class="btn btn--ghost-light btn--lg" href="{{ $secondary }}">{{ $c['secondary_cta'] }}</a>
                @endif
            </div>
        </div>

        @if ($facts)
            {{-- the site signboard: who builds it, under which classification, where --}}
            <aside class="board" aria-label="{{ __('preview.facts.title') }}">
                <p class="board__company">{{ $site->name($locale) }}</p>
                <dl class="board__facts">
                    @foreach ($facts as $fact)
                        <div class="board__row">
                            <dt>{{ $fact['label'] }}</dt>
                            <dd dir="auto">{{ $fact['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </aside>
        @endif
    </div>
</section>
