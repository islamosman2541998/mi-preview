@php
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
@endphp
{{-- A rounded band inside the page width (never edge to edge): photo under the brand colour, or the colour alone. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s cta {{ $img ? 'cta--photo' : 'cta--plain' }}">
    <div class="wrap">
        <div class="cta__band" data-reveal>
            @if ($img)
                <img class="cta__img" src="{{ $img }}" alt="" width="1600" height="560" loading="lazy" decoding="async">
            @endif
            <div class="cta__content">
                @if (filled($c['title'] ?? null))<h2 class="cta__title">{{ $c['title'] }}</h2>@endif
                @if (filled($c['body'] ?? null))<p class="cta__body">{{ $c['body'] }}</p>@endif
                <div class="cta__actions">
                    @if ($wa)
                        <a class="pill pill--accent pill--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="cta"><x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}</a>
                    @endif
                    @if ($site->phoneLink())
                        <a class="pill pill--line pill--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="cta" dir="ltr"><x-preview.icon name="phone" size="18" />{{ $site->phone }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
