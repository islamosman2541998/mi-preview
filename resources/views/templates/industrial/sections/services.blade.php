@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $variant = $section->variant ?? 'cards';
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::Services, $page);
    // the home page shows a selection when the site has a services page of its own
    if ($page->is_home && $more) {
        $items = array_slice($items, 0, 6);
    }
    $photos = collect($items)->contains(fn ($item) => filled($item['img']));
    $headLink = $page->is_home && $more ? ['url' => $more->path($locale), 'label' => __('preview.all_services')] : null;
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--surface services services--{{ $variant }}">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'headLink' => $headLink])

        @if ($variant === 'list')
            {{-- compact rows: icon, scope, what it covers --}}
            <ul class="svc-rows">
                @foreach ($items as $item)
                    <li class="svc-row" data-reveal>
                        <span class="svc__icon"><x-preview.icon :name="\App\Support\ServiceIcons::resolve($item['icon'] ?? null)" size="26" /></span>
                        <h3 class="svc__title">{{ $item['title'] }}</h3>
                        @if (filled($item['description'] ?? null))<p class="svc__desc">{{ $item['description'] }}</p>@endif
                    </li>
                @endforeach
            </ul>
        @else
            <ul class="svc-grid">
                @foreach ($items as $item)
                    @php
                        $icon = \App\Support\ServiceIcons::resolve($item['icon'] ?? null);
                        $ask = $site->whatsappLink(__('preview.service_greeting', ['service' => $item['title']]));
                    @endphp
                    <li class="svc {{ $photos ? 'svc--photo' : 'svc--plain' }}" data-reveal style="--rd: {{ ($loop->index % 3) * 0.08 }}s">
                        @if ($photos)
                            <div class="svc__media {{ $item['img'] ? '' : 'svc__media--tint' }}">
                                @if ($item['img'])
                                    <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="640" height="400" loading="lazy" decoding="async">
                                @else
                                    <x-preview.icon :name="$icon" size="64" />
                                @endif
                            </div>
                        @endif
                        <div class="svc__body">
                            <span class="svc__icon"><x-preview.icon :name="$icon" size="26" /></span>
                            <h3 class="svc__title">{{ $item['title'] }}</h3>
                            @if (filled($item['description'] ?? null))<p class="svc__desc">{{ $item['description'] }}</p>@endif
                            @if ($ask)
                                <a class="svc__link" href="{{ $ask }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="service">
                                    {{ __('preview.request_service') }}<x-preview.icon name="arrow" size="16" />
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
