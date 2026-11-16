@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--tight invite">
    <div class="wrap">
        <div class="invite__panel">
            @if (filled($c['title'] ?? null))<h2 class="invite__title">{{ $c['title'] }}</h2>@endif
            @if (filled($c['body'] ?? null))<p class="invite__body">{{ $c['body'] }}</p>@endif
            <div class="invite__actions">
                @if ($wa)
                    <a class="pill pill--accent pill--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="cta">
                        <x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}
                    </a>
                @endif
                @if ($site->phoneLink())
                    <a class="pill pill--line pill--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="cta">
                        <x-preview.icon name="phone" size="18" /><span dir="ltr">{{ $site->phone }}</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
