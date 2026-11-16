@php($socialIcons = ['facebook' => 'facebook', 'linkedin' => 'linkedin', 'instagram' => 'instagram'])
<footer class="footer">
    <div class="wrap">
        <p class="footer__name">{{ $site->name($locale) }}</p>

        <div class="footer__cols">
            <div>
                @if ($site->industry)<p class="footer__label">{{ $site->industry->name($locale) }}</p>@endif
                @if ($site->address($locale))<p>{{ $site->address($locale) }}</p>@endif
            </div>

            <ul class="footer__links" aria-label="{{ __('preview.pages') }}">
                @foreach ($site->pages as $p)
                    <li><a href="{{ $p->path($locale) }}">{{ $p->title($locale) }}</a></li>
                @endforeach
            </ul>

            <ul class="footer__links" aria-label="{{ __('preview.contact') }}">
                @if ($site->phone)
                    <li><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="footer" dir="ltr">{{ $site->phone }}</a></li>
                @endif
                @if ($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
                    <li><a href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="footer">{{ __('preview.whatsapp') }}</a></li>
                @endif
                @if ($site->email)
                    <li><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></li>
                @endif
            </ul>

            @if (filled(array_filter((array) $site->socials)))
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

        <p class="footer__base">© {{ now()->year }} {{ $site->name($locale) }}. {{ __('preview.rights') }}</p>
    </div>
</footer>
