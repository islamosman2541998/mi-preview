@php($options = array_values(array_filter($c['options'] ?? [])))
{{-- Booking gets real weight here: each option is its own one-tap WhatsApp message. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s booking">
    <div class="wrap">
        <div class="booking__panel">
            <div class="booking__copy">
                @if (filled($c['title'] ?? null))<h2 class="s-head__title">{{ $c['title'] }}</h2>@endif
                @if (filled($c['body'] ?? null))<p class="s-head__intro">{{ $c['body'] }}</p>@endif
            </div>
            <div class="booking__choose">
                @if (count($options))
                    <ul class="booking__options">
                        @foreach ($options as $option)
                            @if ($link = $site->whatsappLink(__('preview.booking_for', ['option' => $option])))
                                <li><a class="pill pill--soft" href="{{ $link }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking_option">{{ $option }}</a></li>
                            @else
                                <li><span class="pill pill--soft">{{ $option }}</span></li>
                            @endif
                        @endforeach
                    </ul>
                @endif
                @if ($wa = $site->whatsappLink(__('preview.booking_greeting')))
                    <a class="pill pill--accent pill--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking">
                        <x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}
                    </a>
                @elseif ($site->phoneLink())
                    <a class="pill pill--accent pill--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="booking">
                        <x-preview.icon name="phone" size="20" />{{ __('preview.call') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
