@php
    $items = collect($c['items'] ?? [])->values();
    $shared = $section->content['shared']['items'] ?? [];
    $variant = $section->variant ?? 'list';
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--surface services services--{{ $variant }}">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])

<<<<<<< Updated upstream
        @if ($variant === 'grid')
            <ul class="tiles">
                @foreach ($items as $i => $item)
                    @php($img = \App\Support\MediaUrl::for($shared[$i]['image'] ?? null))
                    <li class="tile">
                        @if ($img)<img src="{{ $img }}" alt="" width="560" height="360" loading="lazy" decoding="async">@endif
                        <h3 class="tile__title">{{ $item['title'] ?? '' }}</h3>
                        <p>{{ $item['description'] ?? '' }}</p>
                    </li>
                @endforeach
            </ul>
        @else
            {{-- spec-sheet rows: scope on one side, what it covers on the other --}}
            <ul class="spec">
                @foreach ($items as $item)
                    <li class="spec__row">
                        <h3 class="spec__title">{{ $item['title'] ?? '' }}</h3>
                        <p class="spec__desc">{{ $item['description'] ?? '' }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
=======
        <div class="section-slider" data-carousel>
            @include('preview.partials.section-slider-controls')
            @if ($variant === 'list')
                {{-- compact rows: icon, scope, what it covers --}}
                <ul class="svc-rows section-slider__track" data-carousel-track tabindex="0" aria-label="{{ $c['title'] ?? __('preview.services') }}">
                    @foreach ($items as $item)
                        <li class="svc-row" data-reveal>
                            <span class="svc__icon"><x-preview.icon :name="\App\Support\ServiceIcons::resolve($item['icon'] ?? null)" size="26" /></span>
                            <h3 class="svc__title">{{ $item['title'] }}</h3>
                            @if (filled($item['description'] ?? null))<p class="svc__desc">{{ $item['description'] }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            @else
                <ul class="svc-grid section-slider__track" data-carousel-track tabindex="0" aria-label="{{ $c['title'] ?? __('preview.services') }}">
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
>>>>>>> Stashed changes
    </div>
</section>
