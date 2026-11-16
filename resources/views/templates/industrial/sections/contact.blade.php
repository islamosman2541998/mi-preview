@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s contact">
    <div class="wrap contact__grid">
        <div class="contact__intro">
            @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null])
            <div class="contact__actions">
                @if ($wa)
                    <a class="btn btn--signal" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact">
                        <x-preview.icon name="whatsapp" size="18" />{{ __('preview.chat_whatsapp') }}
                    </a>
                @endif
                @if ($site->phoneLink())
                    <a class="btn btn--outline" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact">
                        <x-preview.icon name="phone" size="18" />{{ __('preview.call') }}
                    </a>
                @endif
            </div>
        </div>

        <dl class="contact__list">
            @if ($site->phone)
                <div class="contact__item"><dt><x-preview.icon name="phone" size="18" />{{ __('preview.phone') }}</dt>
                    <dd><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact_list" dir="ltr">{{ $site->phone }}</a></dd></div>
            @endif
            @if ($wa)
                <div class="contact__item"><dt><x-preview.icon name="whatsapp" size="18" />{{ __('preview.whatsapp') }}</dt>
                    <dd><a href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact_list" dir="ltr">{{ $site->whatsapp }}</a></dd></div>
            @endif
            @if ($site->email)
                <div class="contact__item"><dt><x-preview.icon name="mail" size="18" />{{ __('preview.email') }}</dt>
                    <dd><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></dd></div>
            @endif
            @if ($site->address($locale))
                <div class="contact__item"><dt><x-preview.icon name="pin" size="18" />{{ __('preview.address') }}</dt>
                    <dd>{{ $site->address($locale) }}
                        @if ($site->map_url)<a class="contact__map-link" href="{{ $site->map_url }}" target="_blank" rel="noopener">{{ __('preview.directions') }}</a>@endif
                    </dd></div>
            @endif
            @if (filled($c['hours'] ?? null))
                <div class="contact__item"><dt><x-preview.icon name="clock" size="18" />{{ __('preview.hours') }}</dt>
                    <dd>{{ $c['hours'] }}</dd></div>
            @endif
        </dl>
    </div>
</section>
