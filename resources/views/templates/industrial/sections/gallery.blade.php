@php($images = array_values(array_filter($section->content['shared']['images'] ?? [])))
@if (count($images))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s gallery">
    <div class="wrap">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null])
        <div class="gallery__cols">
            @foreach ($images as $image)
                @php($src = \App\Support\MediaUrl::for(is_array($image) ? ($image['path'] ?? null) : $image))
                @continue(! $src)
                <figure class="gallery__item">
                    <img src="{{ $src }}" alt="{{ is_array($image) ? ($image['alt_'.$locale->value] ?? '') : '' }}" loading="lazy" decoding="async" width="600" height="450">
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
