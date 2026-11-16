@php
    $img = \App\Support\MediaUrl::for($c['image'] ?? null);
    $points = array_values(array_filter($c['points'] ?? []));
@endphp
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s about {{ $img ? 'about--photo' : 'about--text' }}">
    <div class="wrap about__grid">
        @if ($img)
            @include('templates.boutique.partials.arch', ['img' => $img, 'alt' => $c['title'] ?? '', 'class' => 'about__arch'])
        @endif
        <div class="about__copy">
            @if (filled($c['title'] ?? null))<h2 class="s-head__title">{{ $c['title'] }}</h2>@endif
            @if (filled($c['body'] ?? null))<p class="about__body">{{ $c['body'] }}</p>@endif
            @if ($points)
                <ul class="chips">
                    @foreach ($points as $point)<li class="chip"><x-preview.icon name="check" size="16" />{{ $point }}</li>@endforeach
                </ul>
            @endif
            @if (filled($c['since'] ?? null))
                <p class="about__since">{{ __('preview.facts.since') }} <strong>{{ $c['since'] }}</strong></p>
            @endif
        </div>
    </div>
</section>
