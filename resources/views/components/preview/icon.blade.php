@props(['name', 'size' => 20])
{{-- Inline stroke icons shared by all templates (no icon font, no emoji). --}}
<svg {{ $attributes->merge(['class' => 'icon icon-'.$name]) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
     fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($name)
    @case('phone')
        <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>
        @break
    @case('mail')
        <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>
        @break
    @case('pin')
        <path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>
        @break
    @case('clock')
        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
        @break
    @case('check')
        <path d="m5 12.5 4.5 4.5L19 7.5"/>
        @break
    @case('menu')
        <path d="M4 7h16M4 12h16M4 17h16"/>
        @break
    @case('close')
        <path d="M6 6l12 12M18 6 6 18"/>
        @break
    @case('chevron')
        <path d="m6 9 6 6 6-6"/>
        @break
    @case('plus')
        <path d="M12 5v14M5 12h14"/>
        @break
    @case('globe')
        <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>
        @break
    @case('external')
        <path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>
        @break
    @case('whatsapp')
        <path stroke="none" fill="currentColor" d="M12 2.2A9.7 9.7 0 0 0 3.6 16.8L2.3 21.7l5-1.3A9.7 9.7 0 1 0 12 2.2zm0 17.7a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 19.9zm4.4-6c-.2-.1-1.4-.7-1.7-.8-.2-.1-.4-.1-.5.1l-.8.9c-.1.2-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.5-.4h-.5a.9.9 0 0 0-.6.3 2.7 2.7 0 0 0-.9 2c0 1.2.9 2.4 1 2.5.1.2 1.7 2.7 4.2 3.8 1.6.7 2.2.7 3 .6.5-.1 1.4-.6 1.6-1.1.2-.6.2-1 .1-1.1l-.5-.2z"/>
        @break
    @case('facebook')
        <path d="M15 8h-2a1 1 0 0 0-1 1v3h3l-.5 3H12v6M9 12h3"/>
        @break
    @case('linkedin')
        <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>
        @break
    @case('instagram')
        <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5v.01"/>
        @break
    @case('tiktok')
        <path d="M14 4v10.5a3.5 3.5 0 1 1-3.5-3.5M14 4c.5 2.5 2 4 4.5 4.5"/>
        @break
    @case('x')
        <path d="M5 4l14 16M19 4 5 20"/>
        @break
    @case('arrow')
        <path d="M5 12h14M13 6l6 6-6 6"/>
        @break
    @case('award')
        <circle cx="12" cy="9" r="5.5"/><path d="m8.5 13.5-1.5 7.5 5-2.5 5 2.5-1.500-7.500"/><path d="m9.800 9 1.600 1.600 2.900-3"/>
        @break
    @case('quote')
        <path stroke="none" fill="currentColor" d="M4 17v-5c0-3.500 2-6 5.500-6.500V8C8 8.500 7 9.500 7 11h2.500v6zm10 0v-5c0-3.500 2-6 5.500-6.500V8c-1.500.5-2.500 1.500-2.500 3h2.500v6z"/>
        @break
    {{-- service icons: keys listed in App\Support\ServiceIcons --}}
    @case('building')
        <path d="M4 21V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16M14 9h5a1 1 0 0 1 1 1v11M2 21h20M8 8h2M8 12h2M8 16h2M17 13v.01M17 17v.01"/>
        @break
    @case('crane')
        <path d="M6 21V4M3 21h6M6 4l14 3.200M6 4 3 7.500h3M6 9h3.500M16 6.300V12M14.500 12h3v2.500h-3z"/>
        @break
    @case('helmet')
        <path d="M3 17.500h18M5 17.500V15a7 7 0 0 1 14 0v2.500M10 8.300V5h4v3.300"/>
        @break
    @case('road')
        <path d="M5 21 9 3M19 21 15 3M12 5v2M12 11v2M12 17v2"/>
        @break
    @case('tools')
        <path d="M13 5h5l2 2v2l-3 1-4-4zM13.500 6.500 4 16l-1 4 4-1 9.500-9.500"/>
        @break
    @case('blueprint')
        <rect x="3" y="4" width="18" height="16" rx="1.500"/><path d="M3 9h7v5h5v6M15 4v5h6"/>
        @break
    @case('shield')
        <path d="M12 3 5 6v5c0 4.500 3 8.300 7 10 4-1.700 7-5.500 7-10V6z"/><path d="m9 12 2.200 2.200L15 10.500"/>
        @break
    @case('flame')
        <path d="M12 3c1 3.500 5 5.500 5 10a5 5 0 0 1-10 0c0-1.800.8-3.200 2-4.200.2 1.600 1 2.400 2 2.700-.6-2.800-.2-5.700 1-8.500z"/>
        @break
    @case('truck')
        <path d="M2 6h11v10H2zM13 9h4l4 4v3h-8"/><circle cx="6.500" cy="17.500" r="2"/><circle cx="17" cy="17.500" r="2"/>
        @break
    @case('box')
        <path d="m12 3 8 4.500v9L12 21l-8-4.500v-9zM4 7.500l8 4.500 8-4.500M12 12v9"/>
        @break
    @case('stethoscope')
        <path d="M6 3v6a4 4 0 0 0 8 0V3M6 3H4.500M14 3h1.500M10 13v2.500a4.500 4.500 0 0 0 9 0V13"/><circle cx="19" cy="11" r="2"/>
        @break
    @case('tooth')
        <path d="M8 3C5.500 3 4 5 4 7.500c0 2 .8 3.400 1.200 5.500.5 2.500.6 8 2.300 8 1.500 0 1.200-5 2.500-5s1 5 2.500 5c1.700 0 1.800-5.500 2.300-8 .4-2.100 1.200-3.500 1.200-5.500C16 5 14.500 3 12 3c-.8 0-1.300.3-2 .3S8.800 3 8 3z"/>
        @break
    @case('heart')
        <path d="M12 20s-7-4.400-7-10a4 4 0 0 1 7-2.600A4 4 0 0 1 19 10c0 5.600-7 10-7 10z"/>
        @break
    @case('scissors')
        <circle cx="6" cy="7" r="2.500"/><circle cx="6" cy="17" r="2.500"/><path d="M8 8.500 20 18M8 15.500 20 6"/>
        @break
    @case('sparkle')
        <path d="m12 3 1.800 5.200L19 10l-5.200 1.800L12 17l-1.800-5.200L5 10l5.200-1.800zM18.500 16v4M16.500 18h4"/>
        @break
    @case('utensils')
        <path d="M7 3v18M4.500 3v5a2.500 2.500 0 0 0 5 0V3M17 21V3c-2 1-3 3.500-3 6.500V13h3"/>
        @break
    @case('coffee')
        <path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5zM17 10h1.500a2.500 2.500 0 0 1 0 5H17M8 3v2.500M12 3v2.500"/>
        @break
    @case('cart')
        <path d="M3 4h2.500l2 11h10l2-8H6.300"/><circle cx="9" cy="19" r="1.500"/><circle cx="17" cy="19" r="1.500"/>
        @break
    @case('tag')
        <path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.500" cy="8.500" r="1.200"/>
        @break
    @case('home')
        <path d="M3 11 12 3l9 8M5 9.500V21h14V9.500M10 21v-6h4v6"/>
        @break
    @case('key')
        <circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M16 7l3 3M14 9l2 2"/>
        @break
    @case('chart')
        <path d="M4 20V4M4 20h16M8 16v-4M12 16V8M16 16v-6"/>
        @break
    @case('users')
        <circle cx="9" cy="8" r="3.500"/><path d="M2.500 20a6.500 6.500 0 0 1 13 0M16 4.800a3.500 3.500 0 0 1 0 6.400M18 14.500a6 6 0 0 1 3.500 5.500"/>
        @break
    @case('wrench')
        <path d="m20 7.500-3 3-2.500-1-1-2.500 3-3a5 5 0 0 0-6.300 6.300l-6.600 6.600a1.900 1.900 0 0 0 2.700 2.700l6.600-6.600A5 5 0 0 0 20 7.500z"/>
        @break
    @case('bolt')
        <path d="M13 3 5 13.500h6L10 21l8-10.500h-6z"/>
        @break
    @case('drop')
        <path d="M12 3s6 6.500 6 11a6 6 0 0 1-12 0c0-4.500 6-11 6-11z"/>
        @break
    @case('leaf')
        <path d="M5 19C5 10 10 5 20 5c0 10-5 15-14 14zM4 20l9-9"/>
        @break
    @case('phone-support')
        <path d="M4 14v-2a8 8 0 0 1 16 0v2M4 14a2 2 0 0 1 2-2h1v6H6a2 2 0 0 1-2-2zM20 14a2 2 0 0 0-2-2h-1v6h1a2 2 0 0 0 2-2zM18 18v1a2 2 0 0 1-2 2h-3"/>
        @break
    @case('star')
        <path d="m12 3 2.700 5.600 6.100.9-4.400 4.300 1 6.100L12 17l-5.400 2.900 1-6.100L3.200 9.500l6.100-.9z"/>
        @break
    @default
        <circle cx="12" cy="12" r="3"/>
@endswitch
</svg>
