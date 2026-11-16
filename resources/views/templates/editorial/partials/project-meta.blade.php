@if (filled($item['location'] ?? null) || filled($item['year'] ?? null) || filled($item['category'] ?? null))
    <dl class="meta">
        @if (filled($item['category'] ?? null))<div><dt>{{ __('preview.project.category') }}</dt><dd>{{ $item['category'] }}</dd></div>@endif
        @if (filled($item['location'] ?? null))<div><dt>{{ __('preview.project.location') }}</dt><dd>{{ $item['location'] }}</dd></div>@endif
        @if (filled($item['year'] ?? null))<div><dt>{{ __('preview.project.year') }}</dt><dd>{{ $item['year'] }}</dd></div>@endif
    </dl>
@endif
