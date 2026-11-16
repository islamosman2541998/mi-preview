@php($wa = $site->whatsappLink(__('preview.whatsapp_greeting')))
{{-- Set like a colophon: who, where, how to reach them. --}}
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s contact">
    <div class="wrap split">
        <div class="split__side">
            @include('templates.editorial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['body'] ?? null])
            @if ($wa)
                <a class="btn" href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact">
                    <x-preview.icon name="whatsapp" size="18" />{{ __('preview.chat_whatsapp') }}
                </a>
            @endif
        </div>
        <dl class="split__main colophon">
            @if ($site->phone)
                <div><dt>{{ __('preview.phone') }}</dt><dd><a href="{{ $site->phoneLink() }}" data-track="phone" data-track-label="contact" dir="ltr">{{ $site->phone }}</a></dd></div>
            @endif
            @if ($wa)
                <div><dt>{{ __('preview.whatsapp') }}</dt><dd><a href="{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp" data-track-label="contact_list" dir="ltr">{{ $site->whatsapp }}</a></dd></div>
            @endif
            @if ($site->email)
                <div><dt>{{ __('preview.email') }}</dt><dd><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></dd></div>
            @endif
            @if ($site->address($locale))
                <div><dt>{{ __('preview.address') }}</dt><dd>{{ $site->address($locale) }}
                    @if ($site->map_url)<a class="text-link" href="{{ $site->map_url }}" target="_blank" rel="noopener">{{ __('preview.directions') }}</a>@endif</dd></div>
            @endif
            @if (filled($c['hours'] ?? null))
                <div><dt>{{ __('preview.hours') }}</dt><dd>{{ $c['hours'] }}</dd></div>
            @endif
        </dl>
    </div>
</section>
