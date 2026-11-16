@php($items = \App\Support\SectionView::items($section, $c, 'items', 'quote'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--surface voices">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null])
        {{-- a row that swipes sideways on phones; authors get an initial, never a stock face --}}
        <ul class="voices__row {{ count($items) === 1 ? 'voices__row--single' : '' }}" data-reveal>
            @foreach ($items as $item)
                <li class="voice">
                    <figure>
                        <x-preview.icon name="quote" size="34" class="voice__mark" />
                        <blockquote><p>{{ $item['quote'] }}</p></blockquote>
                        @if (filled($item['name'] ?? null))
                            <figcaption>
                                <span class="voice__avatar" aria-hidden="true">{{ \App\Support\SectionView::initial($item['name']) }}</span>
                                <span class="voice__by">
                                    <strong class="voice__name">{{ $item['name'] }}</strong>
                                    @if (filled($item['role'] ?? null))<span class="voice__role">{{ $item['role'] }}</span>@endif
                                </span>
                            </figcaption>
                        @endif
                    </figure>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
