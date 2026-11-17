@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
{{-- A dense product grid: price on a solid tag over the photo, a full-width order button under each item. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s products">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="grid-shop" data-reveal>
            @foreach ($items as $item)
                @php($order = $site->whatsappLink(__('preview.order_greeting', ['product' => $item['name']])))
                <li class="sku">
                    <div class="sku__media">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" width="480" height="480" loading="lazy" decoding="async">
                        @else
                            <x-preview.icon name="box" size="52" />
                        @endif
                        <span class="sku__price">@if (filled($item['price'] ?? null)){{ \App\Support\SectionView::number($item['price']) }} {{ __('preview.currency') }}@else{{ __('preview.price_on_request') }}@endif</span>
                    </div>
                    <div class="sku__body">
                        <h3 class="sku__name">{{ $item['name'] }}</h3>
                        @if (filled($item['description'] ?? null))<p class="sku__desc">{{ $item['description'] }}</p>@endif
                    </div>
                    @if ($order)
                        <a class="sku__order" href="{{ $order }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="product_order"><x-preview.icon name="whatsapp" size="18" />{{ __('preview.order') }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
