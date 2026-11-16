{{-- Floating WhatsApp button for the company's own number. --}}
@if ($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
    <a class="wa-float" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="float"
       aria-label="{{ __('preview.float_whatsapp', ['company' => $site->name($locale)]) }}">
        <x-preview.icon name="whatsapp" size="28" />
    </a>
@endif
