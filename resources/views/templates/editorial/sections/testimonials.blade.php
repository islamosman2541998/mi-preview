@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'quote');
    $first = array_shift($items);
@endphp
@if ($first)
{{-- One pull quote set large; the rest as smaller asides. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s voices">
    <div class="wrap">
        @if (filled($c['title'] ?? null))<h2 class="s-head__title voices__title">{{ $c['title'] }}</h2>@endif
        <figure class="pull">
            <blockquote><p>{{ $first['quote'] }}</p></blockquote>
            @if (filled($first['name'] ?? null))
                <figcaption><strong>{{ $first['name'] }}</strong>@if (filled($first['role'] ?? null))<span>{{ $first['role'] }}</span>@endif</figcaption>
            @endif
        </figure>
        @if (count($items))
            <div class="asides">
                @foreach ($items as $item)
                    <figure class="aside">
                        <blockquote><p>{{ $item['quote'] }}</p></blockquote>
                        @if (filled($item['name'] ?? null))
                            <figcaption><strong>{{ $item['name'] }}</strong>@if (filled($item['role'] ?? null))<span>{{ $item['role'] }}</span>@endif</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif
