@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'title');
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::Services, $page);
    if ($page->is_home && $more) {
        $items = array_slice($items, 0, 6);
    }
    $photos = collect($items)->contains(fn ($item) => filled($item['img']));
    $headLink = $page->is_home && $more ? ['url' => $more->path($locale), 'label' => __('preview.all_services')] : null;
@endphp
@if (count($items))
{{-- An index of services: numbered rows; on wide screens the row being read shows its photograph beside the list. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s services">
    <div class="wrap">
        @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'headLink' => $headLink, 'index' => $index ?? null])

        <ol class="index {{ $photos ? 'index--photos' : '' }}" data-reveal>
            @foreach ($items as $item)
                @php($ask = $site->whatsappLink(__('preview.service_greeting', ['service' => $item['title']])))
                <li class="index__row {{ $loop->first ? 'is-first' : '' }}" tabindex="-1">
                    <span class="index__no">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="index__icon"><x-preview.icon :name="\App\Support\ServiceIcons::resolve($item['icon'] ?? null)" size="22" /></span>
                    <div class="index__text">
                        <h3 class="index__title">{{ $item['title'] }}</h3>
                        @if (filled($item['description'] ?? null))<p>{{ $item['description'] }}</p>@endif
                    </div>
                    @if ($ask)
                        <a class="index__ask" href="{{ $ask }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="service" aria-label="{{ __('preview.request_service') }}: {{ $item['title'] }}">
                            <span>{{ __('preview.request_service') }}</span><x-preview.icon name="arrow" size="18" />
                        </a>
                    @endif
                    @if ($item['img'])
                        <img class="index__img" src="{{ $item['img'] }}" alt="" width="560" height="700" loading="lazy" decoding="async">
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
@endif
