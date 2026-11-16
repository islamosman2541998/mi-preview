@php
    $pages = $site->pages;
    $socialIcons = ['facebook' => 'facebook', 'linkedin' => 'linkedin', 'instagram' => 'instagram'];
@endphp
<footer class="footer">
    <div class="wrap footer__grid">
        <div class="footer__about">
            <p class="footer__name">{{ $site->name($locale) }}</p>
            @if ($site->industry)
                <p class="footer__muted">{{ $site->industry->name($locale) }}</p>
            @endif
            @if (filled($site->socials))
                <ul class="footer__social" aria-label="{{ __('preview.follow') }}">
                    @foreach ($site->socials as $network => $url)
                        @continue(blank($url))
                        <li><a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}">
                            <x-preview.icon :name="$socialIcons[$network] ?? 'external'" size="18" />
                        </a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <p class="footer__heading">{{ __('preview.pages') }}</p>
            <ul class="footer__links">
                @foreach ($pages as $p)
                    <li><a href="{{ $p->path($locale) }}">{{ $p->title($locale) }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <p class="footer__heading">{{ __('preview.contact') }}</p>
            <ul class="footer__contact">
                @if ($site->phone)
                    <li><x-preview.icon name="phone" size="16" /><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="footer" dir="ltr">{{ $site->phone }}</a></li>
                @endif
                @if ($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
                    <li><x-preview.icon name="whatsapp" size="16" /><a href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="footer" dir="ltr">{{ $site->whatsapp }}</a></li>
                @endif
                @if ($site->email)
                    <li><x-preview.icon name="mail" size="16" /><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></li>
                @endif
                @if ($site->address($locale))
                    <li><x-preview.icon name="pin" size="16" /><span>{{ $site->address($locale) }}</span></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="wrap footer__base">
        <p>© {{ now()->year }} {{ $site->name($locale) }}. {{ __('preview.rights') }}</p>
    </div>
</footer>
