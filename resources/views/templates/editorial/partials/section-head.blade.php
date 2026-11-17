{{-- Numbered like a magazine feature (01, 02…). $headLink = ['url' => …, 'label' => …] adds a "see all" link. --}}
@if (filled($title ?? null) || filled($intro ?? null))
    <header class="s-head {{ ($headLink ?? null) ? 's-head--split' : '' }}" data-reveal>
        <div class="s-head__text">
            @isset($index)<span class="s-head__no">{{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }}</span>@endisset
            @if (filled($title ?? null))<h2 class="s-head__title">{{ $title }}</h2>@endif
            @if (filled($intro ?? null))<p class="s-head__intro">{{ $intro }}</p>@endif
        </div>
        @if ($headLink ?? null)
            <a class="text-link" href="{{ $headLink['url'] }}">{{ $headLink['label'] }}<x-preview.icon name="arrow" size="16" /></a>
        @endif
    </header>
@endif
