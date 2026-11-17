@php
    $items = \App\Support\SectionView::items($section, $c, 'items', 'q');
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $wa = $site->whatsappLink(__('preview.whatsapp_greeting'));
@endphp
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s faq">
    <div class="wrap faq__grid">
        <div class="faq__main">
            @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null, 'align' => 'start'])
            <div class="faq__list" data-reveal>
                @foreach ($items as $item)
                    <details class="faq__item" @if ($loop->first) open @endif>
                        <summary><span>{{ $item['q'] }}</span><span class="faq__icon"><x-preview.icon name="plus" size="16" /></span></summary>
                        <p>{{ $item['a'] ?? '' }}</p>
                    </details>
                @endforeach
            </div>
        </div>

        @if ($wa || $site->phoneLink())
            <aside class="help" data-reveal>
                @if ($img)
                    @include('templates.boutique.partials.arch', ['img' => $img, 'class' => 'help__arch'])
                @endif
                <div class="help__body">
                    <p class="help__title">{{ __('preview.faq_help.title') }}</p>
                    <p>{{ __('preview.faq_help.body') }}</p>
                    @if ($wa)
                        <a class="pill pill--accent" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="faq"><x-preview.icon name="whatsapp" size="18" />{{ __('preview.chat_whatsapp') }}</a>
                    @else
                        <a class="pill pill--accent" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="faq"><x-preview.icon name="phone" size="18" />{{ __('preview.call') }}</a>
                    @endif
                </div>
            </aside>
        @endif
    </div>
</section>
@endif
