@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? 'list';
@endphp
@if (count($items))
{{-- A bento of solid blocks: the first service is the big one. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--alt services">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="bento">
            @foreach ($items as $item)
                <li class="bento__cell">
                    @if ($variant === 'grid' && $item['img'])
                        <img class="bento__img" src="{{ $item['img'] }}" alt="" width="640" height="400" loading="lazy" decoding="async">
                    @endif
                    <h3 class="bento__title">{{ $item['title'] }}</h3>
                    @if (filled($item['description'] ?? null))<p>{{ $item['description'] }}</p>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
