@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s products">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="catalog">
            @foreach ($items as $item)
                @php($order = $site->whatsappLink(__('preview.order_greeting', ['product' => $item['name']])))
                <li class="product" data-reveal style="--rd: {{ ($loop->index % 4) * 0.06 }}s">
                    <div class="product__visual">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" width="480" height="480" loading="lazy" decoding="async">
                        @else
                            <x-preview.icon name="box" size="56" />
                        @endif
                    </div>
                    <div class="product__body">
                        <h3 class="product__name">{{ $item['name'] }}</h3>
                        @if (filled($item['description'] ?? null))<p class="product__desc">{{ $item['description'] }}</p>@endif
                        <div class="product__foot">
                            <p class="product__price">
                                @if (filled($item['price'] ?? null))
                                    {{ \App\Support\SectionView::number($item['price']) }} <small>{{ __('preview.currency') }}</small>
                                @else
                                    <small>{{ __('preview.price_on_request') }}</small>
                                @endif
                            </p>
                            @if ($order)
                                <a class="btn btn--primary btn--sm" href="{{ $order }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="product_order">
                                    <x-preview.icon name="whatsapp" size="16" />{{ __('preview.order') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
