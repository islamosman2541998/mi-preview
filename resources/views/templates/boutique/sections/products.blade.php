@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s products">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="shelf">
            @foreach ($items as $item)
                @php($order = $site->whatsappLink(__('preview.order_greeting', ['product' => $item['name']])))
                <li class="product" data-reveal>
                    <div class="product__media">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" width="480" height="560" loading="lazy" decoding="async">
                        @else
                            <x-preview.icon name="sparkle" size="48" />
                        @endif
                    </div>
                    <h3 class="product__name">{{ $item['name'] }}</h3>
                    @if (filled($item['description'] ?? null))<p class="product__desc">{{ $item['description'] }}</p>@endif
                    <div class="product__foot">
                        <span class="product__price">@if (filled($item['price'] ?? null)){{ \App\Support\SectionView::number($item['price']) }} <small>{{ __('preview.currency') }}</small>@else<small>{{ __('preview.price_on_request') }}</small>@endif</span>
                        @if ($order)
                            <a class="pill pill--soft pill--sm" href="{{ $order }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="product_order">{{ __('preview.order') }}</a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
