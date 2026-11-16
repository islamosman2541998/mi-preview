@php
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
    $waDigits = preg_replace('/\D+/', '', (string) $site->whatsapp);
    $services = $wa ? \App\Support\PageVisuals::services($site, $locale, 12) : [];
    $formId = 'contact-form-'.$section->id;
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
            {{-- a real enquiry form: it opens WhatsApp with the message written (plain GET to wa.me without JS) --}}
            <form class="form-card" action="https://wa.me/{{ $waDigits }}" method="get" target="_blank"
                  data-wa-form data-wa="{{ $waDigits }}" data-wa-intro="{{ __('preview.form.intro') }}" data-track-label="contact_form" data-reveal>
                <h3 class="form-card__title">{{ __('preview.form.title') }}</h3>
                <div class="form-row">
                    <div class="field">
                        <label for="{{ $formId }}-name">{{ __('preview.form.name') }}</label>
                        <input id="{{ $formId }}-name" type="text" autocomplete="name" required data-wa-field="{{ __('preview.form.name') }}">
                    </div>
                    <div class="field">
                        <label for="{{ $formId }}-phone">{{ __('preview.form.phone') }}</label>
                        <input id="{{ $formId }}-phone" type="tel" autocomplete="tel" dir="ltr" data-wa-field="{{ __('preview.form.phone') }}">
                    </div>
                </div>
                @if (count($services))
                    <div class="field">
                        <label for="{{ $formId }}-service">{{ __('preview.form.service') }}</label>
                        <select id="{{ $formId }}-service" data-wa-field="{{ __('preview.form.service') }}">
                            <option value="">{{ __('preview.form.choose') }}</option>
                            @foreach ($services as $service)
                                <option>{{ $service }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="field">
                    <label for="{{ $formId }}-message">{{ __('preview.form.message') }}</label>
                    <textarea id="{{ $formId }}-message" name="text" rows="4" required data-wa-field="{{ __('preview.form.message') }}"></textarea>
                </div>
                <button type="submit" class="btn btn--signal btn--lg form-card__send">
                    <x-preview.icon name="whatsapp" size="20" />{{ __('preview.form.send') }}
                </button>
                <p class="form-card__note">{{ __('preview.form.note') }}</p>
            </form>
        @endif
    </div>
</section>
