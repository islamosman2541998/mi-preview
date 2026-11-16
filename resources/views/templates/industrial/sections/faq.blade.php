@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'q');
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s faq">
    <div class="wrap faq__grid">
        <div class="faq__side">
            @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])

            @if ($wa || $site->phoneLink())
                <aside class="help-card" data-reveal>
                    @if ($img)
                        <img src="{{ $img }}" alt="" width="640" height="360" loading="lazy" decoding="async">
                    @endif
                    <div class="help-card__body">
                        <h3 class="help-card__title">{{ __('preview.faq_help.title') }}</h3>
                        <p>{{ __('preview.faq_help.body') }}</p>
                        @if ($wa)
                            <a class="btn btn--signal" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="faq">
                                <x-preview.icon name="whatsapp" size="18" />{{ __('preview.chat_whatsapp') }}
                            </a>
                        @else
                            <a class="btn btn--signal" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="faq">
                                <x-preview.icon name="phone" size="18" />{{ __('preview.call') }}
                            </a>
                        @endif
                    </div>
                </aside>
            @endif
        </div>

        <div class="faq__list" data-reveal>
            @foreach ($items as $item)
                <details class="faq__item" @if ($loop->first) open @endif>
                    <summary><span>{{ $item['q'] }}</span><span class="faq__icon"><x-preview.icon name="plus" size="16" /></span></summary>
                    <div class="faq__answer"><p>{{ $item['a'] ?? '' }}</p></div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
