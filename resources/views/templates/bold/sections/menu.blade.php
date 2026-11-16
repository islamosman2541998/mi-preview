@php($categories = \App\Support\SectionView::menu($section, $c))
@if (count($categories))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--alt menu">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="menu__cols">
            @foreach ($categories as $category)
                <div class="menu__col">
                    @if (filled($category['name']))<h3 class="menu__cat">{{ $category['name'] }}</h3>@endif
                    <ul>
                        @foreach ($category['dishes'] as $dish)
                            <li class="menu__dish">
                                <span class="menu__name">{{ $dish['name'] }}</span>
                                @if (filled($dish['price']))<span class="menu__price">{{ $dish['price'] }}</span>@endif
                                @if (filled($dish['description']))<p>{{ $dish['description'] }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <p class="menu__note">{{ __('preview.prices_in') }}</p>
    </div>
</section>
@endif
