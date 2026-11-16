{{-- View tracking (skipped entirely for the owner's browser; the server also re-checks). --}}
@unless (\App\Tracking\OwnerCookie::isOwner(request()))
    <script src="/js/t.js?v={{ @filemtime(public_path('js/t.js')) }}" defer></script>
@endunless
