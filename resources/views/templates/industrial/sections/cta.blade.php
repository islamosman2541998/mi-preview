@php
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="cta {{ $img ? 'cta--photo' : 'cta--solid' }}">
    @if ($img)
        <img class="cta__bg" src="{{ $img }}" alt="" width="1600" height="520" loading="lazy" decoding="async">
    @endif
    <div class="wrap cta__row" data-reveal>
        <div class="cta__copy">
            @if (filled($c['title'] ?? null))<h2 class="cta__title">{{ $c['title'] }}</h2>@endif
            @if (filled($c['body'] ?? null))<p>{{ $c['body'] }}</p>@endif
        </div>
        <div class="cta__actions">
            @if ($wa)
                <a class="btn {{ $img ? 'btn--signal' : 'btn--ink' }} btn--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="cta">
                    <x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}
                </a>
            @endif
            @if ($site->phoneLink())
                <a class="cta__phone" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="cta" dir="ltr">
                    <x-preview.icon name="phone" size="18" />{{ $site->phone }}
                </a>
            @endif
        </div>
    </div>
</section>
