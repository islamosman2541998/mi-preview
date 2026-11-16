{{-- Section heading. $headLink = ['url' => …, 'label' => …] adds a "see all" link beside it. --}}
@if (filled($title ?? null) || filled($intro ?? null))
    <header class="s-head {{ ($headLink ?? null) ? 's-head--split' : '' }}" data-reveal>
        <div class="s-head__text">
            @if (filled($title ?? null))<h2 class="s-head__title">{{ $title }}</h2>@endif
            @if (filled($intro ?? null))<p class="s-head__intro">{{ $intro }}</p>@endif
        </div>
        @if ($headLink ?? null)
            <a class="more-link" href="{{ $headLink['url'] }}">{{ $headLink['label'] }}<x-preview.icon name="arrow" size="18" /></a>
        @endif
    </header>
@endif
