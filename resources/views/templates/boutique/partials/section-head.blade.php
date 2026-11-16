{{-- Centred heading with a short intro underneath. --}}
@if (filled($title ?? null) || filled($intro ?? null))
    <header class="s-head {{ ($align ?? 'center') === 'start' ? 's-head--start' : '' }}">
        @if (filled($title ?? null))<h2 class="s-head__title">{{ $title }}</h2>@endif
        @if (filled($intro ?? null))<p class="s-head__intro">{{ $intro }}</p>@endif
    </header>
@endif
