@php($socialIcons = ['facebook' => 'facebook', 'linkedin' => 'linkedin', 'instagram' => 'instagram'])
<footer class="footer">
    <div class="wrap footer__grid">
        <div>
            <p class="footer__name">{{ $site->name($locale) }}</p>
            @if ($site->industry)<p class="footer__muted">{{ $site->industry->name($locale) }}</p>@endif
        </div>
        <ul class="footer__list" aria-label="{{ __('preview.pages') }}">
            @foreach ($site->pages as $p)
                <li><a href="{{ $p->path($locale) }}">{{ $p->title($locale) }}</a></li>
            @endforeach
        </ul>
        <ul class="footer__list" aria-label="{{ __('preview.contact') }}">
            @if ($site->phone)
                <li><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="footer" dir="ltr">{{ $site->phone }}</a></li>
            @endif
            @if ($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
                <li><a href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="footer">{{ __('preview.whatsapp') }}</a></li>
            @endif
            @if ($site->email)
                <li><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></li>
            @endif
            @if ($site->address($locale))
                <li class="footer__muted">{{ $site->address($locale) }}</li>
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
    </div>
    <div class="wrap footer__base">© {{ now()->year }} {{ $site->name($locale) }}. {{ __('preview.rights') }}</div>
</footer>
