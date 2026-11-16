@php($items = array_values(array_filter($c['items'] ?? [], fn ($i) => filled($i['name'] ?? null))))
@if (count($items))
<section id="{{ $anchor }}" data-section="{{ $anchor }}" class="s s--surface certs">
    <div class="wrap certs__grid">
        @include('templates.industrial.partials.section-head', ['title' => $c['title'] ?? null, 'intro' => $c['intro'] ?? null])

        {{-- a register, not a card row: name and issuing body side by side --}}
        <table class="register">
            <thead class="sr-only">
                <tr><th scope="col">{{ $c['title'] ?? '' }}</th><th scope="col">{{ __('preview.certificate_issuer') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <th scope="row" class="register__name">{{ $item['name'] }}</th>
                        <td class="register__issuer" dir="auto">{{ $item['issuer'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif
