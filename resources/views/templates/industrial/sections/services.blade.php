@php
    $items = collect($c['items'] ?? [])->values();
    $shared = $section->content['shared']['items'] ?? [];
    $variant = $section->variant ?? 'list';
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--surface services services--{{ $variant }}">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])

        @if ($variant === 'grid')
            <ul class="tiles">
                @foreach ($items as $i => $item)
                    @php($img = \App\Support\MediaUrl::for($shared[$i]['image'] ?? null))
                    <li class="tile">
                        @if ($img)<img src="{{ $img }}" alt="" width="560" height="360" loading="lazy" decoding="async">@endif
                        <h3 class="tile__title">{{ $item['title'] ?? '' }}</h3>
                        <p>{{ $item['description'] ?? '' }}</p>
                    </li>
                @endforeach
            </ul>
        @else
            {{-- spec-sheet rows: scope on one side, what it covers on the other --}}
            <ul class="spec">
                @foreach ($items as $item)
                    <li class="spec__row">
                        <h3 class="spec__title">{{ $item['title'] ?? '' }}</h3>
                        <p class="spec__desc">{{ $item['description'] ?? '' }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
