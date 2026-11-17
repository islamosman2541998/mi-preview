@php
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
@endphp
{{-- With a photo: the photograph runs wide and a paper card carries the call. Without: a centred serif line. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="cta {{ $img ? 'cta--photo' : 'cta--type' }}">
    <div class="wrap cta__frame">
        @if ($img)
            <img class="cta__img" src="{{ $img }}" alt="" width="1600" height="700" loading="lazy" decoding="async">
        @endif
        <div class="cta__card" data-reveal>
            @if (filled($c['title'] ?? null))<h2 class="cta__title">{{ $c['title'] }}</h2>@endif
            @if (filled($c['body'] ?? null))<p class="cta__body">{{ $c['body'] }}</p>@endif
            <div class="cta__actions">
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
    </div>
</section>
