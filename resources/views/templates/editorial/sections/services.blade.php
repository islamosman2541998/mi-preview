@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? 'list';
@endphp
@if (count($items))
{{-- Services flow in newspaper columns, each one a short entry with a serif headword. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s services services--{{ $variant }}">
    <div class="wrap">
        @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="entries {{ $variant === 'grid' ? 'entries--pictured' : '' }}">
            @foreach ($items as $item)
                <li class="entry">
                    @if ($variant === 'grid' && $item['img'])
                        <img class="entry__img" src="{{ $item['img'] }}" alt="" width="640" height="420" loading="lazy" decoding="async">
                    @endif
                    <h3 class="entry__title">{{ $item['title'] }}</h3>
                    @if (filled($item['description'] ?? null))<p>{{ $item['description'] }}</p>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
