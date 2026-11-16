@php
    $facts = \App\Support\SiteFacts::for($site, $locale, $c);
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $secondary = \App\Support\PreviewUrl::localize($c['secondary_target'] ?? null, $locale);
    $Heading = $page->is_home ? 'h1' : 'h2';
@endphp
{{-- A site photo under the brand colour; the company facts sit on a card that overlaps the bottom edge. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="hero {{ $img ? 'hero--photo' : 'hero--solid' }} {{ $facts ? 'hero--facts' : '' }}">
    @if ($img)
        <div class="hero__media">
            <img src="{{ $img }}" alt="" width="1600" height="900" fetchpriority="high" decoding="async">
        </div>
    @endif

    <div class="wrap hero__inner">
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
                    <a class="btn btn--ghost-light btn--lg" href="{{ $secondary }}">{{ $c['secondary_cta'] }}<x-preview.icon name="arrow" size="18" /></a>
                @endif
            </div>
        </div>
    </div>

    @if ($facts)
        <div class="wrap hero__facts">
            <ul class="facts" aria-label="{{ __('preview.facts.title') }}">
                @foreach ($facts as $fact)
                    <li class="facts__item">
                        <span class="facts__icon"><x-preview.icon :name="$fact['key'] ?? 'check'" size="22" /></span>
                        <span class="facts__text">
                            <span class="facts__label">{{ $fact['label'] }}</span>
                            <span class="facts__value" dir="auto">{{ $fact['value'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
