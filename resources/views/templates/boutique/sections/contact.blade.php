@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s contact {{ $wa ? 'contact--form' : '' }}">
    <div class="wrap">
        <div class="contact__panel">
            <div class="contact__info" data-reveal>
                @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null, 'align' => 'start'])
                <ul class="contact__list">
                    @if ($site->phone)
                        <li><span class="contact__icon"><x-preview.icon name="phone" size="18" /></span><span><small>{{ __('preview.phone') }}</small><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact_list" dir="ltr">{{ $site->phone }}</a></span></li>
                    @endif
                    @if ($wa)
                        <li><span class="contact__icon"><x-preview.icon name="whatsapp" size="18" /></span><span><small>{{ __('preview.whatsapp') }}</small><a href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact_list" dir="ltr">{{ $site->whatsapp }}</a></span></li>
                    @endif
                    @if ($site->email)
                        <li><span class="contact__icon"><x-preview.icon name="mail" size="18" /></span><span><small>{{ __('preview.email') }}</small><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></span></li>
                    @endif
                    @if (filled($c['hours'] ?? null))
                        <li><span class="contact__icon"><x-preview.icon name="clock" size="18" /></span><span><small>{{ __('preview.hours') }}</small>{{ $c['hours'] }}</span></li>
                    @endif
                    @if ($site->address($locale))
                        <li><span class="contact__icon"><x-preview.icon name="pin" size="18" /></span><span><small>{{ __('preview.address') }}</small>{{ $site->address($locale) }}
                            @if ($site->map_url)<a class="contact__map" href="{{ $site->map_url }}" target="_blank" rel="noopener">{{ __('preview.directions') }}</a>@endif</span></li>
                    @endif
                </ul>
                @if (! $wa && $site->phoneLink())
                    <a class="pill pill--accent pill--lg" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact"><x-preview.icon name="phone" size="18" />{{ __('preview.call') }}</a>
                @endif
            </div>
            @include('preview.partials.contact-form', ['class' => 'soft-form', 'button' => 'pill pill--accent pill--lg'])
        </div>
    </div>
</section>
