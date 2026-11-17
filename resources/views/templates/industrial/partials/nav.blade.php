@php
    $logo = \App\Support\MediaUrl::for($site->logo_nobg_path);
    $currentPath = $page->path($locale);
    $items = $site->menu->reject->is_cta;
    $ctas = $site->menu->filter->is_cta;
@endphp
<header class="masthead" data-masthead>
    {{-- utility strip: the details a procurement officer looks for first --}}
    <div class="utility">
        <div class="wrap utility__row">
            <ul class="utility__list">
                @if ($site->phone)
                    <li><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="utility" dir="ltr"><x-preview.icon name="phone" size="15" />{{ $site->phone }}</a></li>
                @endif
                @if ($site->email)
                    <li class="hide-sm"><a href="mailto:{{ $site->email }}"><x-preview.icon name="mail" size="15" />{{ $site->email }}</a></li>
                @endif
                @if ($site->address($locale))
                    <li class="hide-md"><x-preview.icon name="pin" size="15" />{{ $site->address($locale) }}</li>
                @endif
            </ul>
            <a class="utility__lang" href="{{ $alternateUrl }}" hreflang="{{ $locale->other()->value }}" lang="{{ $locale->other()->value }}">
                <x-preview.icon name="globe" size="15" />{{ __('preview.switch_language') }}
            </a>
        </div>
    </div>

    <div class="wrap navbar">
        <a class="brand" href="{{ \App\Support\PreviewUrl::path($locale) }}">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $site->name($locale) }}" height="44" decoding="async">
            @else
                <span class="brand__name">{{ $site->name($locale) }}</span>
            @endif
        </a>

        <button type="button" class="navbar__toggle" data-nav-toggle aria-expanded="false" aria-controls="site-nav">
            <x-preview.icon name="menu" class="when-closed" />
            <x-preview.icon name="close" class="when-open" />
            <span class="sr-only">{{ __('preview.menu') }}</span>
        </button>

        <nav class="nav" id="site-nav" aria-label="{{ __('preview.menu') }}">
            <ul class="nav__list">
                @foreach ($items as $item)
                    @php($href = $item->href($site, $locale, $homeSlug))
                    @if ($item->children->isNotEmpty())
                        <li class="nav__item has-sub">
                            <button type="button" class="nav__link nav__parent" data-sub-toggle aria-expanded="false">
                                {{ $item->label($locale) }} <x-preview.icon name="chevron" size="16" />
                            </button>
                            <ul class="nav__sub">
                                @foreach ($item->children as $child)
                                    <li><a class="nav__sublink" href="{{ $child->href($site, $locale, $homeSlug) }}"
                                           @if ($t = $child->trackAs()) data-track="{{ $t }}" @endif
                                           @if ($child->type === \App\Enums\NavItemType::External) target="_blank" rel="noopener" @endif>{{ $child->label($locale) }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="nav__item">
                            <a class="nav__link" href="{{ $href }}"
                               @if ($href === $currentPath) aria-current="page" @endif
                               @if ($t = $item->trackAs()) data-track="{{ $t }}" @endif
                               @if ($item->type === \App\Enums\NavItemType::External) target="_blank" rel="noopener" @endif>{{ $item->label($locale) }}</a>
                        </li>
                    @endif
                @endforeach
            </ul>

            @foreach ($ctas as $cta)
                <a class="btn btn--signal nav__cta" href="{{ $cta->href($site, $locale, $homeSlug) }}"
                   @if ($t = $cta->trackAs()) data-track="{{ $t }}" data-track-label="nav" @endif
                   @if (in_array($cta->type, [\App\Enums\NavItemType::Whatsapp, \App\Enums\NavItemType::External])) target="_blank" rel="noopener" @endif>
                    @if ($cta->type === \App\Enums\NavItemType::Whatsapp)<x-preview.icon name="whatsapp" size="18" />@elseif ($cta->type === \App\Enums\NavItemType::Phone)<x-preview.icon name="phone" size="18" />@endif
                    {{ $cta->label($locale) }}
                </a>
            @endforeach
        </nav>
    </div>
</header>
