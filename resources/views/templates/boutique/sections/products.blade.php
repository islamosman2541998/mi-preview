@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s products">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="boutique-grid">
            @foreach ($items as $item)
                <li class="product">
                    <div class="product__media {{ $item['img'] ? '' : 'product__media--empty' }}">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" width="560" height="560" loading="lazy" decoding="async">
                        @else
                            <span aria-hidden="true">{{ \App\Support\SectionView::initial($item['name']) }}</span>
                        @endif
                    </div>
                    <div class="product__info">
                        <h3 class="product__name">{{ $item['name'] }}</h3>
                        <span class="product__price">{{ filled($item['price'] ?? null) ? $item['price'].' '.__('preview.currency') : __('preview.price_on_request') }}</span>
                    </div>
                    @if (filled($item['description'] ?? null))<p class="product__desc">{{ $item['description'] }}</p>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
