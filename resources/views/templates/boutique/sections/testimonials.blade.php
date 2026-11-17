@php($items = \App\Support\SectionView::items($section, $c, 'items', 'quote'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--soft reviews">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="section-slider" data-carousel>
            @include('preview.partials.section-slider-controls')
            <ul class="reviews__list section-slider__track" data-carousel-track tabindex="0" aria-label="{{ $c['title'] ?? '' }}">
                @foreach ($items as $item)
                    <li class="review">
                        <p class="review__stars" aria-hidden="true">@for ($i = 0; $i < 5; $i++)<x-preview.icon name="star" size="16" />@endfor</p>
                        <blockquote><p>{{ $item['quote'] }}</p></blockquote>
                        @if (filled($item['name'] ?? null))
                            <p class="review__by">
                                <span class="review__avatar" aria-hidden="true">{{ \App\Support\SectionView::initial($item['name']) }}</span>
                                <span><strong>{{ $item['name'] }}</strong>@if (filled($item['role'] ?? null))<small>{{ $item['role'] }}</small>@endif</span>
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
@endif
