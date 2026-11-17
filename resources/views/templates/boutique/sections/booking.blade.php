@php
    $options = array_values(array_filter($c['options'] ?? [], fn ($o) => filled($o)));
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $hours = \App\Support\PageVisuals::hours($site, $locale);
@endphp
{{-- Booking is the point of a clinic or salon site: one prominent panel, each option books directly. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s booking">
    <div class="wrap">
        <div class="booking__panel {{ $img ? 'booking__panel--photo' : '' }}" data-reveal>
            @if ($img)
                @include('templates.boutique.partials.arch', ['img' => $img, 'class' => 'booking__arch'])
            @endif
            <div class="booking__body">
                @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null, 'align' => 'start'])
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
                <div class="booking__actions">
                    @if ($wa = $site->whatsappLink(__('preview.booking_greeting')))
                        <a class="pill pill--accent pill--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="booking"><x-preview.icon name="whatsapp" size="20" />{{ $c['button'] ?? __('preview.chat_whatsapp') }}</a>
                    @elseif ($site->phoneLink())
                        <a class="pill pill--accent pill--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="booking"><x-preview.icon name="phone" size="20" />{{ __('preview.call') }}</a>
                    @endif
                    @if ($hours)<p class="booking__hours"><x-preview.icon name="clock" size="18" />{{ $hours }}</p>@endif
                </div>
            </div>
        </div>
    </div>
</section>
