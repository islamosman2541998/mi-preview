@php($items = \App\Support\SectionView::items($section, $c, 'items', 'name'))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s certs">
    <div class="wrap certs__grid">
        @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'index' => $index ?? null])
        <ol class="register" data-reveal>
            @foreach ($items as $item)
                <li class="register__row">
                    <span class="register__seal">
                        @if ($item['img'])<img src="{{ $item['img'] }}" alt="" width="48" height="48" loading="lazy" decoding="async">@else<x-preview.icon name="award" size="24" />@endif
                    </span>
                    <span class="register__name">{{ $item['name'] }}</span>
                    @if (filled($item['issuer'] ?? null))<span class="register__issuer" dir="auto">{{ $item['issuer'] }}</span>@endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
@endif
