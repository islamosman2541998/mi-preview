@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? ($page->is_home ? 'wall' : 'grid');
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::Projects, $page);
    $headLink = $page->is_home && $more ? ['url' => $more->path($locale), 'label' => __('preview.all_projects')] : null;
    if ($variant === 'wall') {
        $items = array_slice($items, 0, 6);
    }
    $categories = collect($items)->pluck('category')->filter()->unique()->values();
    $filterable = $variant === 'grid' && $categories->count() > 1 && count($items) > 3;
    $meta = fn (array $item) => implode(' · ', array_filter([$item['location'] ?? null, $item['year'] ?? null], fn ($v) => filled($v)));
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s projects projects--{{ $variant }}">
    <div class="wrap" @if ($filterable) data-filter-scope @endif>
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'headLink' => $headLink])
        @if ($filterable)
            <div class="filters" role="group" aria-label="{{ __('preview.project.category') }}">
                <button type="button" class="filters__chip" data-filter="*" aria-pressed="true">{{ __('preview.filter_all') }}</button>
                @foreach ($categories as $category)
                    <button type="button" class="filters__chip" data-filter="{{ $category }}" aria-pressed="false">{{ $category }}</button>
                @endforeach
            </div>
        @endif
        <ul class="wall wall--{{ min(count($items), 6) }}" data-reveal>
            @foreach ($items as $item)
                <li class="slab {{ $item['img'] ? '' : 'slab--plain' }}" data-category="{{ $item['category'] ?? '' }}">
                    @if ($item['img'])
                        <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="800" height="600" loading="lazy" decoding="async">
                    @endif
                    <div class="slab__body">
                        @if (filled($item['category'] ?? null))<span class="slab__tag">{{ $item['category'] }}</span>@endif
                        <h3 class="slab__title">{{ $item['title'] }}</h3>
                        @if ($meta($item) !== '')<p class="slab__meta">{{ $meta($item) }}</p>@endif
                        @if (filled($item['description'] ?? null))<p class="slab__desc">{{ $item['description'] }}</p>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
