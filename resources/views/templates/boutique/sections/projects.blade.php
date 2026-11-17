@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? ($page->is_home ? 'rail' : 'grid');
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::Projects, $page);
    $headLink = $page->is_home && $more ? ['url' => $more->path($locale), 'label' => __('preview.all_projects')] : null;
    $categories = collect($items)->pluck('category')->filter()->unique()->values();
    $filterable = $variant === 'grid' && $categories->count() > 1 && count($items) > 3;
    $meta = fn (array $item) => implode(' · ', array_filter([$item['location'] ?? null, $item['year'] ?? null], fn ($v) => filled($v)));
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s works works--{{ $variant }}">
    <div class="wrap" @if ($filterable) data-filter-scope @endif>
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'headLink' => $headLink])

        @if ($filterable)
            <div class="filters" role="group" aria-label="{{ __('preview.project.category') }}">
                <button type="button" class="filters__chip" data-filter="*" aria-pressed="true">{{ __('preview.filter_all') }}</button>
                @foreach ($categories as $category)
                    <button type="button" class="filters__chip" data-filter="{{ $category }}" aria-pressed="false">{{ $category }}</button>
                @endforeach
            </div>
        @endif

        <div class="{{ $variant === 'rail' ? 'section-slider' : '' }}" @if ($variant === 'rail') data-carousel @endif>
            @if ($variant === 'rail')@include('preview.partials.section-slider-controls')@endif
            <ul class="works__list {{ $variant === 'rail' ? 'section-slider__track' : '' }}" @if ($variant === 'rail') data-carousel-track tabindex="0" aria-label="{{ $c['title'] ?? '' }}" @endif>
                @foreach ($items as $item)
                    <li class="work {{ $item['img'] ? '' : 'work--plain' }}" data-category="{{ $item['category'] ?? '' }}" @if ($variant !== 'rail') data-reveal @endif>
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="640" height="800" loading="lazy" decoding="async">
                        @endif
                        <div class="work__body">
                            @if (filled($item['category'] ?? null))<span class="work__tag">{{ $item['category'] }}</span>@endif
                            <h3 class="work__title">{{ $item['title'] }}</h3>
                            @if ($meta($item) !== '')<p class="work__meta">{{ $meta($item) }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
@endif
