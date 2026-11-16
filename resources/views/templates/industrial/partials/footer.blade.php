@php
    $pages = $site->pages;
    $logo = \App\Support\MediaUrl::for($site->logo_nobg_path);
    $about = \App\Support\PageVisuals::aboutSnippet($site, $locale);
    $services = \App\Support\PageVisuals::services($site, $locale);
    $servicesPage = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::Services, $page);
    $servicesUrl = $servicesPage ? $servicesPage->path($locale) : \App\Support\PreviewUrl::path($locale).'#services';
    $hours = \App\Support\PageVisuals::hours($site, $locale);
    $socialIcons = ['facebook' => 'facebook', 'linkedin' => 'linkedin', 'instagram' => 'instagram', 'tiktok' => 'tiktok', 'x' => 'x'];
@endphp
<footer class="footer">
    <div class="wrap footer__grid">
        <div class="footer__brand">
            @if ($logo)
                <img class="footer__logo" src="{{ $logo }}" alt="{{ $site->name($locale) }}" height="52" loading="lazy" decoding="async">
            @else
                <p class="footer__name">{{ $site->name($locale) }}</p>
            @endif
            @if ($about)
                <p class="footer__about">{{ $about }}</p>
            @elseif ($site->industry)
                <p class="footer__about">{{ $site->industry->name($locale) }}</p>
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

        @if ($services)
            <div>
                <p class="footer__heading">{{ __('preview.services') }}</p>
                <ul class="footer__links">
                    @foreach ($services as $service)
                        <li><a href="{{ $servicesUrl }}">{{ $service }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                @if ($hours)
                    <li><x-preview.icon name="clock" size="16" /><span>{{ $hours }}</span></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="footer__base">
        <div class="wrap footer__base-row">
            <p>© {{ now()->year }} {{ $site->name($locale) }}. {{ __('preview.rights') }}</p>
            <a class="footer__top" href="#main">{{ __('preview.back_to_top') }}<x-preview.icon name="chevron" size="16" /></a>
        </div>
    </div>
</footer>
