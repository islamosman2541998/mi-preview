@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
{{-- Split in two blocks: the details on dark, the ways to reach out on light. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="contact">
    <div class="contact__dark">
        <div class="contact__inner">
            @if (filled($c['title'] ?? null))<h2 class="s-head__title">{{ $c['title'] }}</h2>@endif
            <dl class="contact__list">
                @if ($site->phone)
                    <div><dt>{{ __('preview.phone') }}</dt><dd><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact" dir="ltr">{{ $site->phone }}</a></dd></div>
                @endif
                @if ($site->email)
                    <div><dt>{{ __('preview.email') }}</dt><dd><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></dd></div>
                @endif
                @if ($site->address($locale))
                    <div><dt>{{ __('preview.address') }}</dt><dd>{{ $site->address($locale) }}</dd></div>
                @endif
                @if (filled($c['hours'] ?? null))
                    <div><dt>{{ __('preview.hours') }}</dt><dd>{{ $c['hours'] }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
    <div class="contact__light">
        <div class="contact__inner">
            @if (filled($c['body'] ?? null))<p class="contact__body">{{ $c['body'] }}</p>@endif
            <div class="contact__actions">
                @if ($wa)
                    <a class="block-btn block-btn--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact">
                        <x-preview.icon name="whatsapp" size="20" />{{ __('preview.chat_whatsapp') }}
                    </a>
                @endif
                @if ($site->phoneLink())
                    <a class="block-btn block-btn--dark block-btn--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact_button">
                        <x-preview.icon name="phone" size="20" />{{ __('preview.call') }}
                    </a>
                @endif
                @if ($site->map_url)
                    <a class="contact__map" href="{{ $site->map_url }}" target="_blank" rel="noopener"><x-preview.icon name="pin" size="18" />{{ __('preview.directions') }}</a>
                @endif
            </div>
        </div>
    </div>
</section>
