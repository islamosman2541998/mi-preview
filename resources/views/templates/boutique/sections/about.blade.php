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
            <div class="about__media" data-reveal>
                @include('templates.boutique.partials.arch', ['img' => $img, 'class' => 'about__arch', 'alt' => $c['title'] ?? ''])
                @if ($img2)
                    <img class="about__round" src="{{ $img2 }}" alt="" width="320" height="320" loading="lazy" decoding="async">
                @endif
                @if ($years)
                    <p class="about__badge"><strong>{{ $years }}+</strong><span>{{ __('preview.years_experience') }}</span></p>
                @endif
            </div>
        @endif

        <div class="about__copy" data-reveal>
            @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'align' => 'start'])
            @foreach ($paragraphs as $paragraph)
                <p class="about__body">{{ $paragraph }}</p>
            @endforeach
            @if (count($points))
                <ul class="ticks">
                    @foreach ($points as $point)
                        <li><span class="ticks__mark"><x-preview.icon name="check" size="16" /></span>{{ $point }}</li>
                    @endforeach
                </ul>
            @endif
            @if (! $img && $years)
                <p class="about__badge about__badge--inline"><strong>{{ $years }}+</strong><span>{{ __('preview.years_experience') }}</span></p>
            @endif
            <p class="about__sign">{{ $site->name($locale) }}</p>
            @if ($more)
                <a class="pill pill--soft" href="{{ $more->path($locale) }}">{{ __('preview.learn_more') }}<x-preview.icon name="arrow" size="16" /></a>
            @endif
        </div>
    </div>
</section>
