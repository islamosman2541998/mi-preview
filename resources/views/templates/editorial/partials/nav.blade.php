@php
    $logo = \App\Support\MediaUrl::for($site->logo_path ?: $site->logo_nobg_path);
    $currentPath = $page->path($locale);
    $items = $site->menu->reject->is_cta;
    $cta = $site->menu->first->is_cta;
@endphp
{{-- A magazine masthead: the name centred, sections listed underneath. --}}
<header class="masthead" data-masthead>
    <div class="wrap masthead__top">
        <a class="masthead__lang" href="{{ $alternateUrl }}" hreflang="{{ $locale->other()->value }}" lang="{{ $locale->other()->value }}">
            {{ __('preview.switch_language') }}
        </a>

        <a class="brand" href="{{ \App\Support\PreviewUrl::path($locale) }}">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $site->name($locale) }}" height="48" decoding="async">
            @else
                <span class="brand__name">{{ $site->name($locale) }}</span>
            @endif
        </a>

        <div class="masthead__end">
            @if ($cta)
                <a class="masthead__cta" href="{{ $cta->href($site, $locale, $homeSlug) }}"
                   @if ($t = $cta->trackAs()) data-track="{{ $t }}" data-track-label="nav" @endif
                   @if (in_array($cta->type, [\App\Enums\NavItemType::Whatsapp, \App\Enums\NavItemType::External])) target="_blank" rel="noopener" @endif>
                    {{ $cta->label($locale) }}
                </a>
            @endif
            <button type="button" class="masthead__toggle" data-nav-toggle aria-expanded="false" aria-controls="site-nav">
                <x-preview.icon name="menu" class="when-closed" />
                <x-preview.icon name="close" class="when-open" />
                <span class="sr-only">{{ __('preview.menu') }}</span>
            </button>
        </div>
    </div>

    <nav class="nav" id="site-nav" aria-label="{{ __('preview.menu') }}">
        <ul class="wrap nav__list">
            @foreach ($items as $item)
                @php($href = $item->href($site, $locale, $homeSlug))
                @if ($item->children->isNotEmpty())
                    <li class="nav__item has-sub">
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
                    <li class="nav__item">
                        <a class="nav__link" href="{{ $href }}"
                           @if ($href === $currentPath) aria-current="page" @endif
                           @if ($t = $item->trackAs()) data-track="{{ $t }}" @endif
                           @if ($item->type === \App\Enums\NavItemType::External) target="_blank" rel="noopener" @endif>{{ $item->label($locale) }}</a>
                    </li>
                @endif
            @endforeach
            @foreach ($site->menu->filter->is_cta->skip(1) as $extra)
                <li class="nav__item"><a class="nav__link" href="{{ $extra->href($site, $locale, $homeSlug) }}"
                    @if ($t = $extra->trackAs()) data-track="{{ $t }}" data-track-label="nav" @endif>{{ $extra->label($locale) }}</a></li>
            @endforeach
        </ul>
    </nav>
</header>
