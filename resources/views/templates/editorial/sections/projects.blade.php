@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? ($page->is_home ? 'feature' : 'grid');
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::Projects, $page);
    $headLink = $page->is_home && $more ? ['url' => $more->path($locale), 'label' => __('preview.all_projects')] : null;
    if ($variant === 'feature') {
        $items = array_slice($items, 0, 3); // three spreads on the home page
    }
    $categories = collect($items)->pluck('category')->filter()->unique()->values();
    $filterable = $variant !== 'feature' && $categories->count() > 1 && count($items) > 3;
    $meta = fn (array $item) => implode(' · ', array_filter([$item['location'] ?? null, $item['year'] ?? null], fn ($v) => filled($v)));
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s projects projects--{{ $variant }}">
    <div class="wrap" @if ($filterable) data-filter-scope @endif>
        @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'headLink' => $headLink, 'index' => $index ?? null])

        @if ($variant === 'feature')
            {{-- spreads: photograph on one side, the story on the other, alternating --}}
            <div class="spreads">
                @foreach ($items as $item)
                    <article class="spread" data-reveal>
                        <figure class="spread__media {{ $item['img'] ? '' : 'spread__media--type' }}">
                            @if ($item['img'])
                                <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="1000" height="700" loading="lazy" decoding="async">
                                @if ($meta($item) !== '')<figcaption>{{ $meta($item) }}</figcaption>@endif
                            @else
                                <span class="spread__word">{{ $item['category'] ?? $item['title'] }}</span>
                            @endif
                        </figure>
                        <div class="spread__text">
                            <span class="spread__no">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            @if (filled($item['category'] ?? null))<p class="kicker">{{ $item['category'] }}</p>@endif
                            <h3 class="spread__title">{{ $item['title'] }}</h3>
                            @if (filled($item['description'] ?? null))<p class="spread__desc">{{ $item['description'] }}</p>@endif
                            @if (! $item['img'] && $meta($item) !== '')<p class="spread__meta">{{ $meta($item) }}</p>@endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            @if ($filterable)
                <div class="filters" role="group" aria-label="{{ __('preview.project.category') }}">
                    <button type="button" class="filters__chip" data-filter="*" aria-pressed="true">{{ __('preview.filter_all') }}</button>
                    @foreach ($categories as $category)
                        <button type="button" class="filters__chip" data-filter="{{ $category }}" aria-pressed="false">{{ $category }}</button>
                    @endforeach
                </div>
            @endif
            <ul class="pgrid">
                @foreach ($items as $item)
                    <li class="pcard" data-category="{{ $item['category'] ?? '' }}" data-reveal>
                        <figure class="pcard__media {{ $item['img'] ? '' : 'pcard__media--type' }}">
                            @if ($item['img'])
                                <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="800" height="600" loading="lazy" decoding="async">
                            @else
                                <span class="spread__word">{{ $item['category'] ?? $item['title'] }}</span>
                            @endif
                        </figure>
                        @if (filled($item['category'] ?? null))<p class="kicker">{{ $item['category'] }}</p>@endif
                        <h3 class="pcard__title">{{ $item['title'] }}</h3>
                        @if (filled($item['description'] ?? null))<p class="pcard__desc">{{ $item['description'] }}</p>@endif
                        @if ($meta($item) !== '')<p class="pcard__meta">{{ $meta($item) }}</p>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
