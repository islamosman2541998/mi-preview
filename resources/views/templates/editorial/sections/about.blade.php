@php
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $img2 = $img ? \App\Support\MediaUrl::for($c['image2'] ?? null) : null;
    $since = is_numeric($c['since'] ?? null) ? (int) $c['since'] : null;
    $points = array_values(array_filter($c['points'] ?? [], fn ($p) => filled($p)));
    $paragraphs = array_values(array_filter(preg_split('/\R+/u', trim((string) ($c['body'] ?? ''))) ?: []));
    $lede = array_shift($paragraphs);
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::About, $page);
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s about {{ $img ? 'about--photo' : 'about--text' }}">
    <div class="wrap about__grid">
        @if ($img)
            <div class="about__media {{ $img2 ? 'about__media--pair' : '' }}" data-reveal>
                <figure class="about__figure">
                    <img src="{{ $img }}" alt="{{ $c['title'] ?? '' }}" width="720" height="900" loading="lazy" decoding="async">
                    @if ($since)<figcaption>{{ __('preview.facts.since') }} {{ $since }}</figcaption>@endif
                </figure>
                @if ($img2)
                    <img class="about__img2" src="{{ $img2 }}" alt="" width="480" height="600" loading="lazy" decoding="async">
                @endif
            </div>
        @endif

        <div class="about__copy" data-reveal>
            @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'index' => $index ?? null])
            @if ($lede)<p class="about__lede">{{ $lede }}</p>@endif
            @foreach ($paragraphs as $paragraph)
                <p class="about__body">{{ $paragraph }}</p>
            @endforeach

            @if (count($points))
                <ol class="numbered">
                    @foreach ($points as $point)
                        <li><span class="numbered__no">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $point }}</span></li>
                    @endforeach
                </ol>
            @endif

            @if (! $img && $since)
                <p class="about__since"><strong>{{ $since }}</strong> {{ __('preview.facts.since') }}</p>
            @endif

            @if ($more)
                <a class="text-link about__more" href="{{ $more->path($locale) }}">{{ __('preview.learn_more') }}<x-preview.icon name="arrow" size="16" /></a>
            @endif
        </div>
    </div>
</section>
