@php($items = array_values(array_filter($c['items'] ?? [], fn ($i) => filled($i['q'] ?? null))))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s faq">
    <div class="wrap faq__grid">
        <div class="faq__side">
            @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        </div>
        <div class="faq__list">
            @foreach ($items as $item)
                <details class="faq__item">
                    <summary><span>{{ $item['q'] }}</span><x-preview.icon name="plus" size="18" /></summary>
                    <p>{{ $item['a'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
