@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s products">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="goods">
            @foreach ($items as $item)
                <li class="goods__item">
                    <div class="goods__media {{ $item['img'] ? '' : 'goods__media--plain' }}">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" width="520" height="520" loading="lazy" decoding="async">
                        @else
                            <span aria-hidden="true">{{ \App\Support\SectionView::initial($item['name']) }}</span>
                        @endif
                        <span class="goods__price">{{ filled($item['price'] ?? null) ? $item['price'].' '.__('preview.currency') : __('preview.price_on_request') }}</span>
                    </div>
                    <h3 class="goods__name">{{ $item['name'] }}</h3>
                    @if (filled($item['description'] ?? null))<p>{{ $item['description'] }}</p>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
