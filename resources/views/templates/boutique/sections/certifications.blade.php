@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--tight certs">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])
        <ul class="badges">
            @foreach ($items as $item)
                <li class="badge">
                    <x-preview.icon name="check" size="18" />
                    <span><strong>{{ $item['name'] }}</strong>@if (filled($item['issuer'] ?? null))<span dir="auto">{{ $item['issuer'] }}</span>@endif</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
