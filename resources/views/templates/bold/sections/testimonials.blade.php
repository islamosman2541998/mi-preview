@php($items = \App\Support\SectionView::items($section, $c, 'items', 'quote'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--dark voices">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="section-slider" data-carousel>
            @include('preview.partials.section-slider-controls')
            <ul class="voices__list section-slider__track" data-carousel-track tabindex="0" aria-label="{{ $c['title'] ?? '' }}">
                @foreach ($items as $item)
                    <li class="voice">
                        <span class="voice__mark" aria-hidden="true">”</span>
                        <blockquote><p>{{ $item['quote'] }}</p></blockquote>
                        @if (filled($item['name'] ?? null))
                            <p class="voice__by"><strong>{{ $item['name'] }}</strong>@if (filled($item['role'] ?? null))<span>{{ $item['role'] }}</span>@endif</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
@endif
