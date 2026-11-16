@php
    $categories = $c['categories'] ?? [];
    $prices = $section->content['shared']['prices'] ?? [];
@endphp
@if (count($categories))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s menu">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="menu__cols">
            @foreach ($categories as $ci => $category)
                <div class="menu__group">
                    <h3 class="menu__cat">{{ $category['name'] ?? '' }}</h3>
                    <ul class="menu__items">
                        @foreach ($category['items'] ?? [] as $ii => $dish)
                            <li class="menu__item">
                                <div class="menu__line">
                                    <span class="menu__name">{{ $dish['name'] ?? '' }}</span>
                                    <span class="menu__leader" aria-hidden="true"></span>
                                    @if (filled($prices[$ci][$ii] ?? null))
                                        <span class="menu__price">{{ $prices[$ci][$ii] }} {{ __('preview.currency') }}</span>
                                    @endif
                                </div>
                                @if (filled($dish['description'] ?? null))<p class="menu__desc">{{ $dish['description'] }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
