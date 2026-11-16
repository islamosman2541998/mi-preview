@php($items = array_values(array_filter($c['items'] ?? [], fn ($i) => filled($i['quote'] ?? null))))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s testimonials">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="quotes {{ count($items) === 1 ? 'quotes--single' : '' }}">
            @foreach ($items as $item)
                <figure class="quote">
                    <blockquote><p>{{ $item['quote'] }}</p></blockquote>
                    @if (filled($item['name'] ?? null))
                        <figcaption>
                            <span class="quote__name">{{ $item['name'] }}</span>
                            @if (filled($item['role'] ?? null))<span class="quote__role">{{ $item['role'] }}</span>@endif
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
