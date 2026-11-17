{{--
    WhatsApp enquiry form shared by every template: it opens WhatsApp with the message written
    (preview.js), or does a plain GET to wa.me without JS. Renders nothing without a WhatsApp number.
    Expects $site, $locale, $section; optional $class (wrapper) and $button (button classes).
--}}
@php
    $waDigits = preg_replace('/\D+/', '', (string) $site->whatsapp);
    $services = $waDigits !== '' ? \App\Support\PageVisuals::services($site, $locale, 12) : [];
    $formId = 'contact-form-'.$section->id;
@endphp
@if ($waDigits !== '')
    <form class="wa-form {{ $class ?? '' }}" action="https://wa.me/{{ $waDigits }}" method="get" target="_blank"
          data-wa-form data-wa="{{ $waDigits }}" data-wa-intro="{{ __('preview.form.intro') }}" data-track-label="contact_form" data-reveal>
        <h3 class="wa-form__title">{{ __('preview.form.title') }}</h3>
        <div class="wa-form__row">
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
        <button type="submit" class="wa-form__send {{ $button ?? '' }}">
            <x-preview.icon name="whatsapp" size="20" />{{ __('preview.form.send') }}
        </button>
        <p class="wa-form__note">{{ __('preview.form.note') }}</p>
    </form>
@endif
