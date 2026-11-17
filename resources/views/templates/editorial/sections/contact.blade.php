@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
{{-- The company's details set like a letterhead, beside the enquiry form. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s contact {{ $wa ? 'contact--form' : '' }}">
    <div class="wrap contact__grid">
        <div class="letterhead" data-reveal>
            @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null, 'index' => $index ?? null])
            <p class="letterhead__name">{{ $site->name($locale) }}</p>
            <dl class="letterhead__list">
                @if ($site->address($locale))
                    <div><dt>{{ __('preview.address') }}</dt><dd>{{ $site->address($locale) }}
                        @if ($site->map_url)<a class="text-link letterhead__map" href="{{ $site->map_url }}" target="_blank" rel="noopener">{{ __('preview.directions') }}</a>@endif</dd></div>
                @endif
                @if ($site->phone)
                    <div><dt>{{ __('preview.phone') }}</dt><dd><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact_list" dir="ltr">{{ $site->phone }}</a></dd></div>
                @endif
                @if ($wa)
                    <div><dt>{{ __('preview.whatsapp') }}</dt><dd><a href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact_list" dir="ltr">{{ $site->whatsapp }}</a></dd></div>
                @endif
                @if ($site->email)
                    <div><dt>{{ __('preview.email') }}</dt><dd><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></dd></div>
                @endif
                @if (filled($c['hours'] ?? null))
                    <div><dt>{{ __('preview.hours') }}</dt><dd>{{ $c['hours'] }}</dd></div>
                @endif
            </dl>
            @if (! $wa && $site->phoneLink())
                <a class="btn" href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact"><x-preview.icon name="phone" size="18" />{{ __('preview.call') }}</a>
            @endif
        </div>

        @include('preview.partials.contact-form', ['class' => 'paper-form', 'button' => 'btn'])
    </div>
</section>
