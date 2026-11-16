{{-- Title in its own column, intro set beside it (asymmetric 5/7). --}}
@if (filled($title ?? null) || filled($intro ?? null))
    <header class="s-head {{ filled($intro ?? null) ? '' : 's-head--solo' }}">
        @if (filled($title ?? null))<h2 class="s-head__title">{{ $title }}</h2>@endif
        @if (filled($intro ?? null))<p class="s-head__intro">{{ $intro }}</p>@endif
    </header>
@endif
