@php($items = \App\Support\SectionView::items($section, $c, 'items', 'quote'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--dark quotes">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="quotes__grid">
            @foreach ($items as $item)
                <figure class="quote">
                    <span class="quote__mark" aria-hidden="true">”</span>
                    <blockquote><p>{{ $item['quote'] }}</p></blockquote>
                    @if (filled($item['name'] ?? null))
                        <figcaption><strong>{{ $item['name'] }}</strong>@if (filled($item['role'] ?? null)) <span>{{ $item['role'] }}</span>@endif</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
