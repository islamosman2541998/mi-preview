@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--alt certs">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="cells cells--certs">
            @foreach ($items as $item)
                <li class="cells__item">
                    <strong>{{ $item['name'] }}</strong>
                    @if (filled($item['issuer'] ?? null))<span dir="auto">{{ $item['issuer'] }}</span>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
