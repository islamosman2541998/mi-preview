@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="shout">
    <div class="wrap shout__row">
        <div>
            @if (filled($c['title'] ?? null))<h2 class="shout__title">{{ $c['title'] }}</h2>@endif
            @if (filled($c['body'] ?? null))<p class="shout__body">{{ $c['body'] }}</p>@endif
        </div>
        <div class="shout__actions">
            @if ($wa)
                <a class="block-btn block-btn--ink block-btn--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="cta">
                    <x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}
                </a>
            @endif
            @if ($site->phoneLink())
                <a class="shout__phone" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="cta" dir="ltr">{{ $site->phone }}</a>
            @endif
        </div>
    </div>
</section>
