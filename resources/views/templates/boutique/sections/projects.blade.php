@php($items = \App\Support\SectionView::items($section, $c, 'items', 'title'))
@if (count($items))
{{-- Work shown as a swipeable rail of tall, rounded photographs. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s works">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
    </div>
    <ul class="rail" tabindex="0" aria-label="{{ $c['title'] ?? '' }}">
        @foreach ($items as $item)
            <li class="rail__item work">
                <div class="work__media {{ $item['img'] ? '' : 'work__media--empty' }}">
                    @if ($item['img'])
                        <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="600" height="760" loading="lazy" decoding="async">
                    @elseif (filled($item['category'] ?? null))
                        <span>{{ $item['category'] }}</span>
                    @endif
                </div>
                <h3 class="work__title">{{ $item['title'] }}</h3>
                @php($meta = array_filter([$item['location'] ?? null, $item['year'] ?? null]))
                @if ($meta)<p class="work__meta">{{ implode($locale->value === 'ar' ? '، ' : ', ', $meta) }}</p>@endif
                @if (filled($item['description'] ?? null))<p class="work__desc">{{ $item['description'] }}</p>@endif
            </li>
        @endforeach
    </ul>
</section>
@endif
