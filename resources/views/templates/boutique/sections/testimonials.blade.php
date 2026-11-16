@php($items = \App\Support\SectionView::items($section, $c, 'items', 'quote'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--soft reviews">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null])
    </div>
    <ul class="rail rail--reviews" tabindex="0" aria-label="{{ $c['title'] ?? '' }}">
        @foreach ($items as $item)
            <li class="rail__item review">
                <blockquote><p>{{ $item['quote'] }}</p></blockquote>
                @if (filled($item['name'] ?? null))
                    <p class="review__by">
                        <span class="review__initial" aria-hidden="true">{{ \App\Support\SectionView::initial($item['name']) }}</span>
                        <span><strong>{{ $item['name'] }}</strong>@if (filled($item['role'] ?? null))<br>{{ $item['role'] }}@endif</span>
                    </p>
                @endif
            </li>
        @endforeach
    </ul>
</section>
@endif
