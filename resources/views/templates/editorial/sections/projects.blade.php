@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? ($page->is_home ? 'feature' : 'grid');
    $lead = $variant === 'feature' ? array_shift($items) : null;
@endphp
@if ($lead || count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s projects">
    <div class="wrap">
        @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])

        @if ($lead)
            {{-- the cover story: a wide crop and a long caption --}}
            <article class="story story--lead">
                <div class="story__media {{ $lead['img'] ? '' : 'story__media--blank' }}">
                    @if ($lead['img'])
                        <img src="{{ $lead['img'] }}" alt="{{ $lead['title'] }}" width="1400" height="640" loading="lazy" decoding="async">
                    @else
                        <span aria-hidden="true">{{ $lead['category'] ?? '' }}</span>
                    @endif
                </div>
                <div class="story__text">
                    <h3 class="story__title">{{ $lead['title'] }}</h3>
                    @if (filled($lead['description'] ?? null))<p>{{ $lead['description'] }}</p>@endif
                    @include('templates.editorial.partials.project-meta', ['item' => $lead])
                </div>
            </article>
        @endif

        @if (count($items))
            <div class="stories">
                @foreach ($items as $item)
                    <article class="story">
                        <div class="story__media {{ $item['img'] ? '' : 'story__media--blank' }}">
                            @if ($item['img'])
                                <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="800" height="600" loading="lazy" decoding="async">
                            @else
                                <span aria-hidden="true">{{ $item['category'] ?? '' }}</span>
                            @endif
                        </div>
                        <h3 class="story__title">{{ $item['title'] }}</h3>
                        @if (filled($item['description'] ?? null))<p>{{ $item['description'] }}</p>@endif
                        @include('templates.editorial.partials.project-meta', ['item' => $item])
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif
