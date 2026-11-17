@php
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $img2 = $img ? \App\Support\MediaUrl::for($c['image2'] ?? null) : null;
    $since = is_numeric($c['since'] ?? null) ? (int) $c['since'] : null;
    $years = $since ? now()->year - $since : null;
    $years = $years !== null && $years >= 1 && $years <= 150 ? $years : null;
    $points = array_values(array_filter($c['points'] ?? [], fn ($p) => filled($p)));
    $paragraphs = array_values(array_filter(preg_split('/\R+/u', trim((string) ($c['body'] ?? ''))) ?: []));
    $more = \App\Support\PageVisuals::pageWith($site, \App\Enums\SectionType::About, $page);
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s about {{ $img ? 'about--photo' : 'about--text' }}">
    <div class="wrap about__grid">
        @if ($img)
            <div class="about__media {{ $img2 ? 'about__media--pair' : '' }}" data-reveal>
                <img class="about__img" src="{{ $img }}" alt="{{ $c['title'] ?? '' }}" width="720" height="720" loading="lazy" decoding="async">
                @if ($img2)<img class="about__img2" src="{{ $img2 }}" alt="" width="480" height="480" loading="lazy" decoding="async">@endif
                @if ($years)<p class="about__years"><strong>{{ $years }}+</strong><span>{{ __('preview.years_experience') }}</span></p>@endif
            </div>
        @endif

        <div class="about__copy" data-reveal>
            @include('templates.bold.partials.section-head', ['title' => $c['title'] ?? null])
            @foreach ($paragraphs as $paragraph)
                <p class="about__body">{{ $paragraph }}</p>
            @endforeach
            @if (count($points))
                <ul class="checks">
                    @foreach ($points as $point)
                        <li><x-preview.icon name="check" size="18" /><span>{{ $point }}</span></li>
                    @endforeach
                </ul>
            @endif
            @if (! $img && $years)
                <p class="about__years about__years--inline"><strong>{{ $years }}+</strong><span>{{ __('preview.years_experience') }}</span></p>
            @endif
            @if ($more)
                <a class="block-btn block-btn--ink about__more" href="{{ $more->path($locale) }}">{{ __('preview.learn_more') }}<x-preview.icon name="arrow" size="16" /></a>
            @endif
        </div>
    </div>
</section>
