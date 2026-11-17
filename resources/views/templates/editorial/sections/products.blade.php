@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
{{-- A catalogue page: tall photographs, serif names, the price and an order link underneath. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s products">
    <div class="wrap">
        @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'index' => $index ?? null])
        <ul class="catalogue">
            @foreach ($items as $item)
                @php($order = $site->whatsappLink(__('preview.order_greeting', ['product' => $item['name']])))
                <li class="item" data-reveal>
                    <figure class="item__media {{ $item['img'] ? '' : 'item__media--type' }}">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" width="560" height="700" loading="lazy" decoding="async">
                        @else
                            <span class="spread__word">{{ $item['name'] }}</span>
                        @endif
                    </figure>
                    <h3 class="item__name">{{ $item['name'] }}</h3>
                    @if (filled($item['description'] ?? null))<p class="item__desc">{{ $item['description'] }}</p>@endif
                    <div class="item__foot">
                        <span class="item__price">
                            @if (filled($item['price'] ?? null)){{ \App\Support\SectionView::number($item['price']) }} {{ __('preview.currency') }}@else{{ __('preview.price_on_request') }}@endif
                        </span>
                        @if ($order)
                            <a class="text-link" href="{{ $order }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="product_order">{{ __('preview.order') }}<x-preview.icon name="arrow" size="16" /></a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
