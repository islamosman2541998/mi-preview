@php
    $options = array_values(array_filter($c['options'] ?? [], fn ($o) => filled($o)));
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s booking {{ $img ? 'booking--photo' : '' }}">
    <div class="wrap booking__grid">
        <div class="booking__copy" data-reveal>
            @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null, 'index' => $index ?? null])
            @if (count($options))
                <ul class="booking__options">
                    @foreach ($options as $option)
                        @if ($optionLink = $site->whatsappLink(__('preview.booking_for', ['option' => $option])))
                            <li><a href="{{ $optionLink }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking_option"><span>{{ $option }}</span><x-preview.icon name="arrow" size="16" /></a></li>
                        @else
                            <li><span>{{ $option }}</span></li>
                        @endif
                    @endforeach
                </ul>
            @endif
            @if ($wa = $site->whatsappLink(__('preview.booking_greeting')))
                <a class="btn" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking"><x-preview.icon name="whatsapp" size="18" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}</a>
            @elseif ($site->phoneLink())
                <a class="btn" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="booking"><x-preview.icon name="phone" size="18" />{{ __('preview.call') }}</a>
            @endif
        </div>
        @if ($img)
            <img class="booking__img" src="{{ $img }}" alt="" width="720" height="900" loading="lazy" decoding="async" data-reveal>
        @endif
    </div>
</section>
