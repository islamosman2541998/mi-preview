@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? ($page->is_home ? 'feature' : 'grid');
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::Projects, $page);
    $headLink = $page->is_home && $more ? ['url' => $more->path($locale), 'label' => __('preview.all_projects')] : null;

    if ($variant === 'feature') {
        $items = array_slice($items, 0, 5); // the mosaic holds five at most
    }

    $categories = collect($items)->pluck('category')->filter()->unique()->values();
    $filterable = $variant !== 'feature' && $categories->count() > 1 && count($items) > 3;
    $meta = fn (array $item) => implode(' · ', array_filter([$item['location'] ?? null, $item['year'] ?? null], fn ($v) => filled($v)));
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s projects projects--{{ $variant }}">
    <div class="wrap" @if ($filterable) data-filter-scope @endif>
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'headLink' => $headLink])

        @if ($variant === 'feature')
            {{-- mosaic: the lead project large, the rest as tiles; a project without a photo is a colour tile --}}
            <ul class="mosaic mosaic--{{ count($items) }}" data-reveal>
                @foreach ($items as $item)
                    <li class="tile {{ $loop->first ? 'tile--lead' : '' }} {{ $item['img'] ? '' : 'tile--plain' }}">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="900" height="640" loading="lazy" decoding="async">
                        @endif
                        <div class="tile__body">
                            @if (filled($item['category'] ?? null))<span class="tile__tag">{{ $item['category'] }}</span>@endif
                            <h3 class="tile__title">{{ $item['title'] }}</h3>
                            @if ($meta($item) !== '')<p class="tile__meta">{{ $meta($item) }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
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
                    <li class="pcard" data-category="{{ $item['category'] ?? '' }}" data-reveal style="--rd: {{ ($loop->index % 3) * 0.08 }}s">
                        <div class="pcard__visual {{ $item['img'] ? '' : 'pcard__visual--plain' }}">
                            @if ($item['img'])
                                <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="800" height="600" loading="lazy" decoding="async">
                            @else
                                <span class="pcard__plate">{{ $item['category'] ?? $item['title'] }}</span>
                            @endif
                            @if ($item['img'] && filled($item['category'] ?? null))<span class="tile__tag pcard__tag">{{ $item['category'] }}</span>@endif
                        </div>
                        <div class="pcard__body">
                            <h3 class="pcard__title">{{ $item['title'] }}</h3>
                            @if (filled($item['description'] ?? null))<p class="pcard__desc">{{ $item['description'] }}</p>@endif
                            <dl class="pcard__meta">
                                @if (filled($item['location'] ?? null))
                                    <div><dt><x-preview.icon name="pin" size="15" /><span class="sr-only">{{ __('preview.project.location') }}</span></dt><dd>{{ $item['location'] }}</dd></div>
                                @endif
                                @if (filled($item['year'] ?? null))
                                    <div><dt><x-preview.icon name="clock" size="15" /><span class="sr-only">{{ __('preview.project.year') }}</span></dt><dd>{{ $item['year'] }}</dd></div>
                                @endif
                            </dl>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
