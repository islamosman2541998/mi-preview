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
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s about {{ $img ? 'about--media' : 'about--text' }}">
    <div class="wrap about__grid">
        @if ($img)
            {{-- two site photos, the smaller one overlapping, with the years-in-business plate --}}
            <div class="about__media {{ $img2 ? 'about__media--duo' : '' }}" data-reveal>
                <img class="about__img" src="{{ $img }}" alt="{{ $c['title'] ?? '' }}" width="720" height="820" loading="lazy" decoding="async">
                @if ($img2)
                    <img class="about__img2" src="{{ $img2 }}" alt="" width="420" height="420" loading="lazy" decoding="async">
                @endif
                @if ($years)
                    <p class="about__badge"><strong>{{ $years }}+</strong><span>{{ __('preview.years_experience') }}</span></p>
                @endif
            </div>
        @endif

        <div class="about__copy" data-reveal>
            @if (filled($c['title'] ?? null))<h2 class="s-head__title">{{ $c['title'] }}</h2>@endif
            @foreach ($paragraphs as $paragraph)
                <p class="about__body">{{ $paragraph }}</p>
            @endforeach

            @if ($img && count($points))
                <ul class="checklist">
                    @foreach ($points as $point)
                        <li><x-preview.icon name="check" size="18" />{{ $point }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($more)
                <a class="btn btn--outline about__more" href="{{ $more->path($locale) }}">{{ __('preview.learn_more') }}<x-preview.icon name="arrow" size="18" /></a>
            @endif
        </div>

        @if (! $img && (count($points) || $years))
            {{-- no photo: the points become a panel of their own beside the text --}}
            <div class="about__panel" data-reveal>
                @if ($years)
                    <p class="about__years"><strong>{{ $years }}+</strong><span>{{ __('preview.years_experience') }}</span></p>
                @endif
                @if (count($points))
                    <ul class="checklist">
                        @foreach ($points as $point)
                            <li><x-preview.icon name="check" size="18" />{{ $point }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
</section>
