@php
    $options = array_values(array_filter($c['options'] ?? [], fn ($o) => filled($o)));
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s booking {{ $img ? 'booking--photo' : '' }}">
    <div class="wrap booking__grid">
        @if ($img)
            <figure class="booking__media" data-reveal>
                <img src="{{ $img }}" alt="" width="720" height="560" loading="lazy" decoding="async">
            </figure>
        @endif

        <div class="booking__panel" data-reveal>
            @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null])

            @if (count($options))
                <ul class="booking__options">
                    @foreach ($options as $option)
                        @if ($optionLink = $site->whatsappLink(__('preview.booking_for', ['option' => $option])))
                            <li><a class="chip" href="{{ $optionLink }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking_option">{{ $option }}</a></li>
                        @else
                            <li><span class="chip">{{ $option }}</span></li>
                        @endif
                    @endforeach
                </ul>
            @endif

            @if ($wa = $site->whatsappLink(__('preview.booking_greeting')))
                <a class="btn btn--signal btn--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking">
                    <x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}
                </a>
            @elseif ($site->phoneLink())
                <a class="btn btn--signal btn--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="booking">
                    <x-preview.icon name="phone" size="20" />{{ __('preview.call') }}
                </a>
            @endif
        </div>
    </div>
</section>
