@php
    $items = array_values(array_filter($c['items'] ?? [], fn ($i) => filled($i['name'] ?? null)));
    $shared = $section->content['shared']['items'] ?? [];
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s products">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="catalog">
            @foreach ($items as $i => $item)
                @php($img = \App\Support\MediaUrl::for($shared[$i]['image'] ?? null))
                <li class="catalog__item">
                    <div class="catalog__visual {{ $img ? '' : 'catalog__visual--empty' }}">
                        @if ($img)<img src="{{ $img }}" alt="{{ $item['name'] }}" width="480" height="480" loading="lazy" decoding="async">@endif
                    </div>
                    <h3 class="catalog__name">{{ $item['name'] }}</h3>
                    @if (filled($item['description'] ?? null))<p class="catalog__desc">{{ $item['description'] }}</p>@endif
                    <p class="catalog__price">
                        @if (filled($shared[$i]['price'] ?? null))
                            {{ $shared[$i]['price'] }} {{ __('preview.currency') }}
                        @else
                            {{ __('preview.price_on_request') }}
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
