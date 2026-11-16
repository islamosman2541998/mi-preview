@php($options = array_values(array_filter($c['options'] ?? [])))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s booking">
    <div class="wrap split">
        <div class="split__side">
            @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null])
        </div>
        <div class="split__main">
            @if (count($options))
                <ul class="booking__options">
                    @foreach ($options as $option)
                        @if ($link = $site->whatsappLink(__('preview.booking_for', ['option' => $option])))
                            <li><a href="{{ $link }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking_option">{{ $option }}</a></li>
                        @else
                            <li><span>{{ $option }}</span></li>
                        @endif
                    @endforeach
                </ul>
            @endif
            @if ($wa = $site->whatsappLink(__('preview.booking_greeting')))
                <a class="btn" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking">
                    <x-preview.icon name="whatsapp" size="18" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}
                </a>
            @elseif ($site->phoneLink())
                <a class="btn" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="booking">{{ __('preview.call') }}</a>
            @endif
        </div>
    </div>
</section>
