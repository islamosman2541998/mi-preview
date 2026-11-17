@php
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
@endphp
{{-- A full-width brand block with an oversized line; a photo panel takes one side when there is one. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="cta {{ $img ? 'cta--photo' : '' }}">
    <div class="cta__block" data-reveal>
        @if (filled($c['title'] ?? null))<h2 class="cta__title">{{ $c['title'] }}</h2>@endif
        @if (filled($c['body'] ?? null))<p class="cta__body">{{ $c['body'] }}</p>@endif
        <div class="cta__actions">
            @if ($wa)
                <a class="block-btn block-btn--ink block-btn--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="cta"><x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}</a>
            @endif
            @if ($site->phoneLink())
                <a class="block-btn block-btn--outline block-btn--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="cta" dir="ltr"><x-preview.icon name="phone" size="18" />{{ $site->phone }}</a>
            @endif
        </div>
    </div>
    @if ($img)
        <img class="cta__img" src="{{ $img }}" alt="" width="900" height="600" loading="lazy" decoding="async">
    @endif
</section>
