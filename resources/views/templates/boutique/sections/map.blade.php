@php($embed = $section->content['shared']['embed_url'] ?? null)
@if (filled($embed) && str_starts_with($embed, 'https://'))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--tight map">
    <div class="wrap">
        @if (filled($c['title'] ?? null))<h2 class="sr-only">{{ $c['title'] }}</h2>@endif
        <iframe class="map__frame" src="{{ $embed }}" title="{{ $c['title'] ?? $site->name($locale) }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    </div>
</section>
@endif
