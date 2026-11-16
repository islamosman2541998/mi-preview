@php($embed = $section->content['shared']['embed_url'] ?? null)
@if (filled($embed) && str_starts_with($embed, 'https://'))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--tight map">
    <figure class="wrap map__figure">
        <iframe src="{{ $embed }}" title="{{ $c['title'] ?? $site->name($locale) }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        @if ($site->address($locale))<figcaption>{{ $site->address($locale) }}</figcaption>@endif
    </figure>
</section>
@endif
