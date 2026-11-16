@php
    $logo = \App\Support\MediaUrl::for($site->logo_nobg_path ?: $site->logo_path);
    $socialIcons = ['facebook' => 'facebook', 'linkedin' => 'linkedin', 'instagram' => 'instagram'];
@endphp
<footer class="footer">
    <div class="wrap footer__inner">
        @if ($logo)
            <img class="footer__logo" src="{{ $logo }}" alt="{{ $site->name($locale) }}" height="56" loading="lazy" decoding="async">
        @else
            <p class="footer__name">{{ $site->name($locale) }}</p>
        @endif

        <ul class="footer__pages" aria-label="{{ __('preview.pages') }}">
            @foreach ($site->pages as $p)
                <li><a href="{{ $p->path($locale) }}">{{ $p->title($locale) }}</a></li>
            @endforeach
        </ul>

        <ul class="footer__contact" aria-label="{{ __('preview.contact') }}">
            @if ($site->phone)
                <li><x-preview.icon name="phone" size="16" /><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="footer" dir="ltr">{{ $site->phone }}</a></li>
            @endif
            @if ($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
                <li><x-preview.icon name="whatsapp" size="16" /><a href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="footer">{{ __('preview.whatsapp') }}</a></li>
            @endif
            @if ($site->address($locale))
                <li><x-preview.icon name="pin" size="16" /><span>{{ $site->address($locale) }}</span></li>
            @endif
        </ul>

        @if (filled(array_filter((array) $site->socials)))
            <ul class="footer__social" aria-label="{{ __('preview.follow') }}">
                @foreach ($site->socials as $network => $url)
                    @continue(blank($url))
                    <li><a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}"><x-preview.icon :name="$socialIcons[$network] ?? 'external'" size="18" /></a></li>
                @endforeach
            </ul>
        @endif

        <p class="footer__base">© {{ now()->year }} {{ $site->name($locale) }}. {{ __('preview.rights') }}</p>
    </div>
</footer>
