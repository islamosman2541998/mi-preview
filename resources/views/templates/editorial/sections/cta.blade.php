@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
{{-- The closing line of the feature: one large sentence and one way to act. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s closing">
    <div class="wrap closing__inner">
        @if (filled($c['title'] ?? null))<h2 class="closing__title">{{ $c['title'] }}</h2>@endif
        @if (filled($c['body'] ?? null))<p class="closing__body">{{ $c['body'] }}</p>@endif
        <div class="closing__actions">
            @if ($wa)
                <a class="btn" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="cta">
                    <x-preview.icon name="whatsapp" size="18" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}
                </a>
            @endif
            @if ($site->phoneLink())
                <a class="text-link" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="cta" dir="ltr">{{ $site->phone }}</a>
            @endif
        </div>
    </div>
</section>
