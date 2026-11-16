@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? 'list';
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--soft services">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])

        @if ($variant === 'grid')
            <ul class="treatments">
                @foreach ($items as $item)
                    <li class="treatment">
                        <div class="treatment__media {{ $item['img'] ? '' : 'treatment__media--empty' }}">
                            @if ($item['img'])<img src="{{ $item['img'] }}" alt="" width="640" height="480" loading="lazy" decoding="async">@endif
                        </div>
                        <h3 class="treatment__title">{{ $item['title'] }}</h3>
                        @if (filled($item['description'] ?? null))<p>{{ $item['description'] }}</p>@endif
                    </li>
                @endforeach
            </ul>
        @else
            {{-- a service menu card: one panel, the services listed inside it --}}
            <ul class="menu-card">
                @foreach ($items as $item)
                    <li class="menu-card__row">
                        <h3 class="menu-card__title">{{ $item['title'] }}</h3>
                        @if (filled($item['description'] ?? null))<p>{{ $item['description'] }}</p>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
