@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
{{-- Two panels: when and where to visit (hours first — that's what people check), and how to reach. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s contact">
    <div class="wrap">
        @include('templates.boutique.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null])
        <div class="contact__panels">
            <div class="panel">
                @if (filled($c['hours'] ?? null))
                    <p class="panel__label"><x-preview.icon name="clock" size="18" />{{ __('preview.hours') }}</p>
                    <p class="panel__big">{{ $c['hours'] }}</p>
                @endif
                @if ($site->address($locale))
                    <p class="panel__label"><x-preview.icon name="pin" size="18" />{{ __('preview.address') }}</p>
                    <p>{{ $site->address($locale) }}</p>
                    @if ($site->map_url)<a class="pill pill--soft" href="{{ $site->map_url }}" target="_blank" rel="noopener">{{ __('preview.directions') }}</a>@endif
                @endif
            </div>
            <div class="panel panel--tint">
                <ul class="panel__lines">
                    @if ($site->phone)
                        <li><x-preview.icon name="phone" size="18" /><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact" dir="ltr">{{ $site->phone }}</a></li>
                    @endif
                    @if ($site->email)
                        <li><x-preview.icon name="mail" size="18" /><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></li>
                    @endif
                </ul>
                @if ($wa)
                    <a class="pill pill--accent pill--lg" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact">
                        <x-preview.icon name="whatsapp" size="20" />{{ __('preview.chat_whatsapp') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
