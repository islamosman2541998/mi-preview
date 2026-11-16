@php
    $logo = \App\Support\MediaUrl::for($site->logo_nobg_path ?: $site->logo_path);
    $currentPath = $page->path($locale);
    $items = $site->menu->reject->is_cta;
    $ctas = $site->menu->filter->is_cta;
@endphp
<header class="masthead" data-masthead>
    <div class="wrap bar">
        <a class="brand" href="{{ \App\Support\PreviewUrl::path($locale) }}">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $site->name($locale) }}" height="40" decoding="async">
            @else
                <span class="brand__name">{{ $site->name($locale) }}</span>
            @endif
        </a>

        <nav class="nav" id="site-nav" aria-label="{{ __('preview.menu') }}">
            <ul class="nav__list">
                @foreach ($items as $item)
                    @php($href = $item->href($site, $locale, $homeSlug))
                    @if ($item->children->isNotEmpty())
                        <li class="has-sub">
                            <button type="button" class="nav__link" data-sub-toggle aria-expanded="false">
                                {{ $item->label($locale) }} <x-preview.icon name="chevron" size="14" />
                            </button>
                            <ul class="nav__sub">
                                @foreach ($item->children as $child)
                                    <li><a href="{{ $child->href($site, $locale, $homeSlug) }}"
                                           @if ($t = $child->trackAs()) data-track="{{ $t }}" @endif
                                           @if ($child->type === \App\Enums\NavItemType::External) target="_blank" rel="noopener" @endif>{{ $child->label($locale) }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li><a class="nav__link" href="{{ $href }}"
                               @if ($href === $currentPath) aria-current="page" @endif
                               @if ($t = $item->trackAs()) data-track="{{ $t }}" @endif
                               @if ($item->type === \App\Enums\NavItemType::External) target="_blank" rel="noopener" @endif>{{ $item->label($locale) }}</a></li>
                    @endif
                @endforeach
                <li><a class="nav__link nav__lang" href="{{ $alternateUrl }}" hreflang="{{ $locale->other()->value }}" lang="{{ $locale->other()->value }}">{{ __('preview.switch_language') }}</a></li>
            </ul>
            @foreach ($ctas as $cta)
                <a class="block-btn nav__cta" href="{{ $cta->href($site, $locale, $homeSlug) }}"
                   @if ($t = $cta->trackAs()) data-track="{{ $t }}" data-track-label="nav" @endif
                   @if (in_array($cta->type, [\App\Enums\NavItemType::Whatsapp, \App\Enums\NavItemType::External])) target="_blank" rel="noopener" @endif>
                    {{ $cta->label($locale) }}
                </a>
            @endforeach
        </nav>

        <button type="button" class="bar__toggle" data-nav-toggle aria-expanded="false" aria-controls="site-nav">
            <x-preview.icon name="menu" class="when-closed" />
            <x-preview.icon name="close" class="when-open" />
            <span class="sr-only">{{ __('preview.menu') }}</span>
        </button>
    </div>
</header>
