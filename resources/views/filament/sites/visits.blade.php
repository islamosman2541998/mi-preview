@php
    use App\Tracking\VisitMessages;
    $fmt = fn (int $s) => VisitMessages::duration($s);
    $device = fn ($v) => match ($v->device?->value) { 'mobile' => 'موبايل', 'tablet' => 'تابلت', default => 'كمبيوتر' };
@endphp
<x-filament-panels::page>
    {{-- summary --}}
    <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
        @foreach ([
            ['الزيارات', number_format($summary['visits'])],
            ['زوار مختلفين', number_format($summary['visitors'])],
            ['متوسط مدة الزيارة', $fmt($summary['avg'])],
            ['زيارات بها تواصل', number_format($summary['whatsapp'])],
        ] as [$label, $value])
            <x-filament::section compact>
                <div style="font-size:.8rem;opacity:.7">{{ $label }}</div>
                <div style="font-size:1.5rem;font-weight:700;margin-top:2px">{{ $value }}</div>
            </x-filament::section>
        @endforeach
    </div>

    @forelse ($visits as $v)
        @php($m = $v['model'])
        <x-filament::section
            collapsible
            :collapsed="! $v['highlight'] && ! $loop->first"
            :id="'visit-'.$m->id"
            @style(['outline: 2px solid var(--primary-500); outline-offset: 2px' => $v['highlight']])
        >
            <x-slot name="heading">
                <span style="display:inline-flex;flex-wrap:wrap;align-items:center;gap:8px">
                    <span>{{ $m->started_at->translatedFormat('j F Y — g:i a') }}</span>
                    @if ($m->is_return)<x-filament::badge color="warning">زيارة متكررة</x-filament::badge>@endif
                    @if ($m->isOpen())<x-filament::badge color="success">الآن</x-filament::badge>@endif
                    @if ($v['demoBar'])<x-filament::badge color="danger">أريد هذا الموقع</x-filament::badge>@endif
                    @if ($v['whatsapp'])<x-filament::badge color="success">واتساب</x-filament::badge>@endif
                    @if ($v['phone'])<x-filament::badge color="info">اتصال</x-filament::badge>@endif
                </span>
            </x-slot>
            <x-slot name="description">
                {{ $fmt($m->duration_seconds) }}
                · {{ $device($m) }}
                @if ($m->country) · {{ $m->country }} @endif
                @if ($m->referrer) · من {{ parse_url($m->referrer, PHP_URL_HOST) }} @endif
                · {{ count($v['pages']) }} {{ count($v['pages']) === 1 ? 'صفحة' : 'صفحات' }}
            </x-slot>

            <div style="display:grid;gap:24px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
                <div>
                    <div style="font-weight:600;margin-bottom:10px">وقت المشاهدة لكل قسم</div>
                    @forelse ($v['dwell'] as $d)
                        <div style="display:grid;grid-template-columns:8.5em 1fr 4.5em;align-items:center;gap:10px;margin-bottom:8px;font-size:.875rem">
                            <span>{{ $d['label'] }}</span>
                            <span style="height:10px;border-radius:5px;background:var(--gray-200)">
                                <span style="display:block;height:100%;width:{{ max(3, $d['pct']) }}%;border-radius:5px;background:var(--primary-500)"></span>
                            </span>
                            <span style="opacity:.75;text-align:end">{{ $fmt($d['seconds']) }}</span>
                        </div>
                    @empty
                        <div style="opacity:.6;font-size:.875rem">لا توجد بيانات أقسام (زيارة قصيرة).</div>
                    @endforelse
                </div>

                <div>
                    <div style="font-weight:600;margin-bottom:10px">التسلسل</div>
                    <ol style="display:grid;gap:6px;font-size:.875rem">
                        @foreach ($v['timeline'] as $e)
                            <li style="display:grid;grid-template-columns:5.5em 1fr;gap:10px">
                                <span style="opacity:.6;font-variant-numeric:tabular-nums">{{ $e['time']->format('g:i:s') }}</span>
                                <span @style(['font-weight:600;color:var(--danger-600)' => $e['hot']])>{{ $e['text'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <div style="margin-top:12px;font-size:.75rem;opacity:.55" dir="ltr">{{ \Illuminate\Support\Str::limit($m->user_agent, 110) }}</div>
                </div>
            </div>
        </x-filament::section>
    @empty
        <x-filament::section>
            <div style="text-align:center;padding:24px 0">
                <div style="font-weight:600">لم يفتح أحد الموقع بعد</div>
                <div style="opacity:.7;margin-top:4px">انسخ رسالة واتساب من صفحة الموقع وأرسلها للعميل — هيوصلك تنبيه على تيليجرام أول ما يفتح.</div>
            </div>
        </x-filament::section>
    @endforelse

    @if ($hasMore)
        <div style="text-align:center">
            <x-filament::button color="gray" wire:click="loadMore">عرض المزيد ({{ $total - $limit }})</x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
