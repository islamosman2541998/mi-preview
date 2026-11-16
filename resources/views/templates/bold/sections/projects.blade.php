@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? ($page->is_home ? 'feature' : 'grid');
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s projects">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="tiles tiles--{{ $variant }}">
            @foreach ($items as $item)
                <li class="tile {{ $item['img'] ? '' : 'tile--plain' }}">
                    @if ($item['img'])
                        <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="900" height="640" loading="lazy" decoding="async">
                    @endif
                    <div class="tile__label">
                        @if (filled($item['category'] ?? null))<span class="tile__cat">{{ $item['category'] }}</span>@endif
                        <h3 class="tile__title">{{ $item['title'] }}</h3>
                        @php($meta = array_filter([$item['location'] ?? null, $item['year'] ?? null]))
                        @if ($meta)<p class="tile__meta">{{ implode($locale->value === 'ar' ? '، ' : ', ', $meta) }}</p>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
