{{-- Agency demo bar: small, dismissible for the session. --}}
<div class="demo-bar" data-demo-bar role="region" aria-label="{{ __('preview.demo.cta') }}">
    {{-- names isolated with <bdi> so Latin brand names don't reorder the Arabic sentence --}}
    <p class="demo-bar__text">{!! __('preview.demo.label', [
        'company' => '<bdi>'.e($site->name($locale)).'</bdi>',
        'brand' => '<bdi>'.e(\App\Support\Brand::name()).'</bdi>',
    ]) !!}</p>
    @if ($brandLink = \App\Support\Brand::whatsappLink($site))
        <a class="demo-bar__cta" href="{{ $brandLink }}" target="_blank" rel="noopener" data-track="cta" data-track-label="demo_bar">
            {{ __('preview.demo.cta') }}
        </a>
    @endif
    <button type="button" class="demo-bar__close" data-demo-bar-close aria-label="{{ __('preview.demo.dismiss') }}">
        <x-preview.icon name="close" size="16" />
    </button>
</div>
