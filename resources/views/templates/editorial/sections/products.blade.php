@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s products">
    <div class="wrap">
        @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="shelf">
            @foreach ($items as $item)
                <li class="shelf__item">
                    <div class="shelf__media {{ $item['img'] ? '' : 'shelf__media--blank' }}">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" width="640" height="800" loading="lazy" decoding="async">
                        @else
                            <span aria-hidden="true">{{ \App\Support\SectionView::initial($item['name']) }}</span>
                        @endif
                    </div>
                    <h3 class="shelf__name">{{ $item['name'] }}</h3>
                    @if (filled($item['description'] ?? null))<p>{{ $item['description'] }}</p>@endif
                    <p class="shelf__price">{{ filled($item['price'] ?? null) ? $item['price'].' '.__('preview.currency') : __('preview.price_on_request') }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
