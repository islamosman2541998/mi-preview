{{-- Centred heading; $headLink = ['url' => …, 'label' => …] adds a "see all" pill under it. $align = 'start' for side layouts. --}}
@if (filled($title ?? null) || filled($intro ?? null))
    <header class="s-head {{ ($align ?? 'center') === 'start' ? 's-head--start' : '' }}" data-reveal>
        @if (filled($title ?? null))<h2 class="s-head__title">{{ $title }}</h2>@endif
        @if (filled($intro ?? null))<p class="s-head__intro">{{ $intro }}</p>@endif
        @if ($headLink ?? null)
            <a class="more" href="{{ $headLink['url'] }}">{{ $headLink['label'] }}<x-preview.icon name="arrow" size="16" /></a>
        @endif
    </header>
@endif
