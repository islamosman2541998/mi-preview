@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="cta-band">
    <div class="wrap cta-band__row">
        <div class="cta-band__copy">
            @if (filled($c['title'] ?? null))<h2 class="cta-band__title">{{ $c['title'] }}</h2>@endif
            @if (filled($c['body'] ?? null))<p>{{ $c['body'] }}</p>@endif
        </div>
        <div class="cta-band__actions">
            @if ($wa)
                <a class="btn btn--ink btn--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="cta">
                    <x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}
                </a>
            @endif
            @if ($site->phoneLink())
                <a class="cta-band__phone" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="cta" dir="ltr">
                    <x-preview.icon name="phone" size="18" />{{ $site->phone }}
                </a>
            @endif
        </div>
    </div>
</section>
