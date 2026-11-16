{{-- Logo preloader: once per browser session, hidden on load or after 2.5s (see preview.js). --}}
@if ($site->preloader_enabled)
    <div class="preloader" data-preloader aria-hidden="true">
        <div class="preloader__mark">
            @if ($logo = \App\Support\MediaUrl::for($site->logo_nobg_path ?: $site->logo_path))
                <img src="{{ $logo }}" alt="" width="160" height="80" decoding="async">
            @else
                <span class="preloader__name">{{ $site->name($locale) }}</span>
            @endif
            <span class="preloader__track"><span class="preloader__fill"></span></span>
        </div>
    </div>
@endif
