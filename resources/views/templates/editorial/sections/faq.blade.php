@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'q');
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s faq">
    <div class="wrap faq__grid">
        <div class="faq__side">
            @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'index' => $index ?? null])
            @if ($img)
                <img class="faq__img" src="{{ $img }}" alt="" width="640" height="480" loading="lazy" decoding="async" data-reveal>
            @endif
            @if ($wa || $site->phoneLink())
                <aside class="faq__help" data-reveal>
                    <p class="faq__help-title">{{ __('preview.faq_help.title') }}</p>
                    <p>{{ __('preview.faq_help.body') }}</p>
                    @if ($wa)
                        <a class="text-link" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="faq">{{ __('preview.chat_whatsapp') }}<x-preview.icon name="arrow" size="16" /></a>
                    @else
                        <a class="text-link" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="faq">{{ __('preview.call') }}<x-preview.icon name="arrow" size="16" /></a>
                    @endif
                </aside>
            @endif
        </div>

        <div class="faq__list" data-reveal>
            @foreach ($items as $item)
                <details class="faq__item" @if ($loop->first) open @endif>
                    <summary>
                        <span class="faq__no">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="faq__q">{{ $item['q'] }}</span>
                        <x-preview.icon name="plus" size="18" class="faq__icon" />
                    </summary>
                    <p>{{ $item['a'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
