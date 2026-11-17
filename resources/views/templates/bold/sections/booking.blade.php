@php
    $options = array_values(array_filter($c['options'] ?? [], fn ($o) => filled($o)));
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="booking {{ $img ? 'booking--photo' : '' }}">
    <div class="booking__block" data-reveal>
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null])
        @if (count($options))
            <ul class="booking__options">
                @foreach ($options as $option)
                    @if ($optionLink = $site->whatsappLink(__('preview.booking_for', ['option' => $option])))
                        <li><a class="booking__option" href="{{ $optionLink }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking_option">{{ $option }}<x-preview.icon name="arrow" size="16" /></a></li>
                    @else
                        <li><span class="booking__option">{{ $option }}</span></li>
                    @endif
                @endforeach
            </ul>
        @endif
        @if ($wa = $site->whatsappLink(__('preview.booking_greeting')))
            <a class="block-btn block-btn--ink block-btn--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking"><x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}</a>
        @elseif ($site->phoneLink())
            <a class="block-btn block-btn--ink block-btn--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="booking"><x-preview.icon name="phone" size="20" />{{ __('preview.call') }}</a>
        @endif
    </div>
    @if ($img)
        <img class="booking__img" src="{{ $img }}" alt="" width="900" height="700" loading="lazy" decoding="async">
    @endif
</section>
