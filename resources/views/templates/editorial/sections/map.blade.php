@php($embed = $section->content['shared']['embed_url'] ?? null)
@if (filled($embed) && str_starts_with($embed, 'https://'))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="map">
    <div class="wrap">
        <figure class="map__figure">
            <iframe class="map__frame" src="{{ $embed }}" title="{{ $c['title'] ?? $site->name($locale) }}"
                    loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            @if (filled($c['title'] ?? null) || $site->address($locale))
                <figcaption>{{ $c['title'] ?? '' }}@if (filled($c['title'] ?? null) && $site->address($locale)) — @endif{{ $site->address($locale) }}</figcaption>
            @endif
        </figure>
    </div>
</section>
@endif
