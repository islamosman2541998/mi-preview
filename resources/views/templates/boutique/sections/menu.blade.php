@php($categories = \App\Support\SectionView::menu($section, $c))
@if (count($categories))
{{-- One menu card, categories inside it in two columns. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--soft menu">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => __('preview.prices_in')])
        <div class="menu__card" data-reveal>
            @foreach ($categories as $category)
                <div class="menu__group">
                    @if (filled($category['name']))<h3 class="menu__cat">{{ $category['name'] }}</h3>@endif
                    <ul>
                        @foreach ($category['dishes'] as $dish)
                            <li class="menu__item">
                                <div class="menu__line">
                                    <span class="menu__name">{{ $dish['name'] }}</span>
                                    <span class="menu__leader" aria-hidden="true"></span>
                                    @if (filled($dish['price']))<span class="menu__price">{{ \App\Support\SectionView::number($dish['price']) }} {{ __('preview.currency') }}</span>@endif
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
