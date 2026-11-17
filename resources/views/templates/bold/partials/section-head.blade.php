{{-- Heavy heading with an optional "see all" block link beside it. --}}
@if (filled($title ?? null) || filled($intro ?? null))
    <header class="s-head {{ ($headLink ?? null) ? 's-head--split' : '' }}" data-reveal>
        <div class="s-head__text">
            @if (filled($title ?? null))<h2 class="s-head__title">{{ $title }}</h2>@endif
            @if (filled($intro ?? null))<p class="s-head__intro">{{ $intro }}</p>@endif
        </div>
        @if ($headLink ?? null)
            <a class="block-btn block-btn--outline" href="{{ $headLink['url'] }}">{{ $headLink['label'] }}<x-preview.icon name="arrow" size="16" /></a>
        @endif
    </header>
@endif
