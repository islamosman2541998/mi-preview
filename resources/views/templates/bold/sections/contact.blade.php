@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
{{-- Dark details block and light form block side by side. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s contact {{ $wa ? 'contact--form' : '' }}">
    <div class="wrap contact__halves">
        <div class="contact__dark" data-reveal>
            @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null])
            <dl class="contact__list">
                @if ($site->phone)
                    <div><dt><x-preview.icon name="phone" size="18" />{{ __('preview.phone') }}</dt><dd><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact_list" dir="ltr">{{ $site->phone }}</a></dd></div>
                @endif
                @if ($wa)
                    <div><dt><x-preview.icon name="whatsapp" size="18" />{{ __('preview.whatsapp') }}</dt><dd><a href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact_list" dir="ltr">{{ $site->whatsapp }}</a></dd></div>
                @endif
                @if ($site->email)
                    <div><dt><x-preview.icon name="mail" size="18" />{{ __('preview.email') }}</dt><dd><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></dd></div>
                @endif
                @if (filled($c['hours'] ?? null))
                    <div><dt><x-preview.icon name="clock" size="18" />{{ __('preview.hours') }}</dt><dd>{{ $c['hours'] }}</dd></div>
                @endif
                @if ($site->address($locale))
                    <div><dt><x-preview.icon name="pin" size="18" />{{ __('preview.address') }}</dt><dd>{{ $site->address($locale) }}
                        @if ($site->map_url)<a class="contact__map" href="{{ $site->map_url }}" target="_blank" rel="noopener">{{ __('preview.directions') }}</a>@endif</dd></div>
                @endif
            </dl>
            @if (! $wa && $site->phoneLink())
                <a class="block-btn block-btn--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact"><x-preview.icon name="phone" size="18" />{{ __('preview.call') }}</a>
            @endif
        </div>
        @include('preview.partials.contact-form', ['class' => 'block-form', 'button' => 'block-btn block-btn--lg'])
    </div>
</section>
