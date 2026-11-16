@php
    $items = collect($c['items'] ?? [])->values();
    $shared = $section->content['shared']['items'] ?? [];
    $variant = $section->variant ?? ($page->is_home ? 'feature' : 'grid');
@endphp
@if ($items->isNotEmpty())
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s projects projects--{{ $variant }}">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])

        <ul class="projects__list">
            @foreach ($items as $i => $item)
                @php($img = \App\Support\MediaUrl::for($shared[$i]['image'] ?? null))
                <li class="project {{ $variant === 'feature' && $i === 0 ? 'project--lead' : '' }}">
                    <div class="project__visual {{ $img ? '' : 'project__visual--plate' }}">
                        @if ($img)
                            <img src="{{ $img }}" alt="{{ $item['title'] ?? '' }}" width="800" height="560" loading="lazy" decoding="async">
                        @elseif (filled($item['category'] ?? null))
                            <span class="project__plate">{{ $item['category'] }}</span>
                        @endif
                    </div>
                    <div class="project__body">
                        <h3 class="project__title">{{ $item['title'] ?? '' }}</h3>
                        @if (filled($item['description'] ?? null))
                            <p class="project__desc">{{ $item['description'] }}</p>
                        @endif
                        <dl class="project__meta">
                            @if (filled($item['location'] ?? null))
                                <div><dt>{{ __('preview.project.location') }}</dt><dd>{{ $item['location'] }}</dd></div>
                            @endif
                            @if (filled($shared[$i]['year'] ?? null))
                                <div><dt>{{ __('preview.project.year') }}</dt><dd>{{ $shared[$i]['year'] }}</dd></div>
                            @endif
                            @if (filled($item['category'] ?? null) && $img)
                                <div><dt>{{ __('preview.project.category') }}</dt><dd>{{ $item['category'] }}</dd></div>
                            @endif
                        </dl>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
