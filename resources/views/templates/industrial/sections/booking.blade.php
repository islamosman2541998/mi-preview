@php($options = array_values(array_filter($c['options'] ?? [])))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--surface booking">
    <div class="wrap booking__grid">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null])

        <div class="booking__panel">
            @if (count($options))
                <ul class="booking__options">
                    @foreach ($options as $option)
                        @if ($link = $site->whatsappLink(__('preview.booking_for', ['option' => $option])))
                            <li><a class="chip" href="{{ $link }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking_option">{{ $option }}</a></li>
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
