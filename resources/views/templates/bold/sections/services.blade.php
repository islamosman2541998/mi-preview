@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::Services, $page);
    if ($page->is_home && $more) {
        $items = array_slice($items, 0, 5);
    }
    $headLink = $page->is_home && $more ? ['url' => $more->path($locale), 'label' => __('preview.all_services')] : null;
@endphp
@if (count($items))
{{-- A bento: the first service is the big photo block, the rest solid cells with their icons. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--alt services">
    <div class="wrap">
        @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'headLink' => $headLink])
        <ul class="bento bento--{{ min(count($items), 6) }}" data-reveal>
            @foreach ($items as $item)
                @php
                    $icon = \App\Support\ServiceIcons::resolve($item['icon'] ?? null);
                    $ask = $site->whatsappLink(__('preview.service_greeting', ['service' => $item['title']]));
                @endphp
                <li class="cell {{ $loop->first ? 'cell--lead' : '' }} {{ $item['img'] ? 'cell--photo' : '' }}">
                    @if ($item['img'])
                        <img class="cell__img" src="{{ $item['img'] }}" alt="" width="800" height="600" loading="lazy" decoding="async">
                    @endif
                    <span class="cell__icon"><x-preview.icon :name="$icon" size="26" /></span>
                    <div class="cell__text">
                        <h3 class="cell__title">{{ $item['title'] }}</h3>
                        @if (filled($item['description'] ?? null))<p class="cell__desc">{{ $item['description'] }}</p>@endif
                        @if ($ask)
                            <a class="cell__link" href="{{ $ask }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="service">{{ __('preview.request_service') }}<x-preview.icon name="arrow" size="16" /></a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
