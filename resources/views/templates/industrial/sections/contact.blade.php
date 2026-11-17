@php
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s contact {{ $wa ? 'contact--form' : '' }}">
    <div class="wrap contact__grid">
        <div class="contact__info" data-reveal>
            @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null])

            <ul class="contact__cards">
                @if ($site->phone)
                    <li class="ccard">
                        <span class="ccard__icon"><x-preview.icon name="phone" size="20" /></span>
                        <span class="ccard__text"><span class="ccard__label">{{ __('preview.phone') }}</span>
                            <a class="ccard__value" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact_list" dir="ltr">{{ $site->phone }}</a></span>
                    </li>
                @endif
                @if ($wa)
                    <li class="ccard">
                        <span class="ccard__icon"><x-preview.icon name="whatsapp" size="20" /></span>
                        <span class="ccard__text"><span class="ccard__label">{{ __('preview.whatsapp') }}</span>
                            <a class="ccard__value" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact_list" dir="ltr">{{ $site->whatsapp }}</a></span>
                    </li>
                @endif
                @if ($site->email)
                    <li class="ccard">
                        <span class="ccard__icon"><x-preview.icon name="mail" size="20" /></span>
                        <span class="ccard__text"><span class="ccard__label">{{ __('preview.email') }}</span>
                            <a class="ccard__value" href="mailto:{{ $site->email }}">{{ $site->email }}</a></span>
                    </li>
                @endif
                @if (filled($c['hours'] ?? null))
                    <li class="ccard">
                        <span class="ccard__icon"><x-preview.icon name="clock" size="20" /></span>
                        <span class="ccard__text"><span class="ccard__label">{{ __('preview.hours') }}</span>
                            <span class="ccard__value">{{ $c['hours'] }}</span></span>
                    </li>
                @endif
                @if ($site->address($locale))
                    <li class="ccard ccard--wide">
                        <span class="ccard__icon"><x-preview.icon name="pin" size="20" /></span>
                        <span class="ccard__text"><span class="ccard__label">{{ __('preview.address') }}</span>
                            <span class="ccard__value">{{ $site->address($locale) }}</span>
                            @if ($site->map_url)<a class="ccard__link" href="{{ $site->map_url }}" target="_blank" rel="noopener">{{ __('preview.directions') }}</a>@endif
                        </span>
                    </li>
                @endif
            </ul>

            @if (! $wa && $site->phoneLink())
                <a class="btn btn--signal btn--lg contact__call" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact">
                    <x-preview.icon name="phone" size="20" />{{ __('preview.call') }}
                </a>
            @endif
        </div>

        @if ($wa)
            @include('preview.partials.contact-form', ['class' => 'form-card', 'button' => 'btn btn--signal btn--lg'])
        @endif
    </div>
</section>
