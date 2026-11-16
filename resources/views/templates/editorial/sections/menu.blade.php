@php($categories = \App\Support\SectionView::menu($section, $c))
@if (count($categories))
{{-- A printed bill of fare: one narrow centred column. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s carte">
    <div class="wrap carte__inner">
        @if (filled($c['title'] ?? null))<h2 class="carte__title">{{ $c['title'] }}</h2>@endif
        @foreach ($categories as $category)
            <div class="carte__group">
                @if (filled($category['name']))<h3 class="carte__cat">{{ $category['name'] }}</h3>@endif
                <ul>
                    @foreach ($category['dishes'] as $dish)
                        <li class="carte__dish">
                            <span class="carte__name">{{ $dish['name'] }}</span>
                            @if (filled($dish['price']))<span class="carte__price">{{ $dish['price'] }} {{ __('preview.currency') }}</span>@endif
                            @if (filled($dish['description']))<p>{{ $dish['description'] }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</section>
@endif
