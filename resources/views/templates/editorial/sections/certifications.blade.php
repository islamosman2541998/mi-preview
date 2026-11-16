@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s certs">
    <div class="wrap split">
        <div class="split__side">
            @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        </div>
        <dl class="split__main certs__list">
            @foreach ($items as $item)
                <div><dt>{{ $item['name'] }}</dt>@if (filled($item['issuer'] ?? null))<dd dir="auto">{{ $item['issuer'] }}</dd>@endif</div>
            @endforeach
        </dl>
    </div>
</section>
@endif
