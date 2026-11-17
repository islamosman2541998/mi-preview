@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::Services, $page);
    $photos = collect($items)->contains(fn ($item) => filled($item['img']));
    $headLink = $page->is_home && $more ? ['url' => $more->path($locale), 'label' => __('preview.all_services')] : null;
@endphp
@if (count($items))
{{-- Treatments / services as a slider of cards with arched photographs. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--soft services">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'headLink' => $headLink])
        <div class="section-slider" data-carousel>
            @include('preview.partials.section-slider-controls')
            <ul class="treats section-slider__track" data-carousel-track tabindex="0" aria-label="{{ $c['title'] ?? __('preview.services') }}">
                @foreach ($items as $item)
                    @php
                        $icon = \App\Support\ServiceIcons::resolve($item['icon'] ?? null);
                        $ask = $site->whatsappLink(__('preview.service_greeting', ['service' => $item['title']]));
                    @endphp
                    <li class="treat {{ $photos ? 'treat--photo' : 'treat--plain' }}">
                        @if ($photos)
                            <div class="treat__media">
                                @if ($item['img'])
                                    <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" width="560" height="640" loading="lazy" decoding="async">
                                @else
                                    <x-preview.icon :name="$icon" size="56" />
                                @endif
                            </div>
                        @endif
                        <span class="treat__icon"><x-preview.icon :name="$icon" size="24" /></span>
                        <h3 class="treat__title">{{ $item['title'] }}</h3>
                        @if (filled($item['description'] ?? null))<p class="treat__desc">{{ $item['description'] }}</p>@endif
                        @if ($ask)
                            <a class="treat__link" href="{{ $ask }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="service">{{ __('preview.request_service') }}<x-preview.icon name="arrow" size="16" /></a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
@endif
