{{-- AI run status for the site being edited (App\Filament\Widgets\AiRunProgress). --}}
<x-filament-widgets::widget>
    @if ($run)
        <div @if ($run->isActive()) wire:poll.3s @endif>
            <x-filament::section compact>
                <div style="display:grid;gap:10px">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px">
                        <div style="display:flex;align-items:center;gap:10px;font-weight:600">
                            @if ($run->isActive())
                                <x-filament::loading-indicator style="width:18px;height:18px" />
                                {{ $run->isWholeSite() ? 'جارٍ توليد محتوى الموقع بالذكاء الاصطناعي' : 'جارٍ إعادة توليد '.count($run->section_ids).' '.(count($run->section_ids) === 1 ? 'قسم' : 'أقسام') }}
                            @elseif ($run->status === \App\Models\AiRun::DONE)
                                <x-filament::icon icon="heroicon-s-check-circle" style="width:20px;height:20px;color:var(--success-600)" />
                                اكتمل التوليد
                            @else
                                <x-filament::icon icon="heroicon-s-exclamation-triangle" style="width:20px;height:20px;color:var(--danger-600)" />
                                تعذّر التوليد
                            @endif
                        </div>

                        <div style="display:flex;gap:8px">
                            @if (! $run->isActive())
                                @if ($run->status === \App\Models\AiRun::DONE)
                                    <x-filament::button size="sm" icon="heroicon-o-arrow-path" x-on:click="window.location.reload()">
                                        عرض النتيجة
                                    </x-filament::button>
                                @endif
                                <x-filament::button size="sm" color="gray" wire:click="dismiss">إخفاء</x-filament::button>
                            @endif
                        </div>
                    </div>

                    @if ($run->isActive())
                        <div style="height:8px;border-radius:4px;background:var(--gray-200);overflow:hidden">
                            <div style="height:100%;width:{{ max(3, $run->progress) }}%;background:var(--primary-500);transition:width .6s"></div>
                        </div>
                        <div style="font-size:.85rem;opacity:.75">
                            @if ($run->status === \App\Models\AiRun::QUEUED)
                                في الانتظار — يبدأ خلال دقيقة تقريبًا.
                                @if ($queueHint && app()->isLocal())
                                    <span dir="ltr" style="opacity:.8">(locally: php artisan queue:work)</span>
                                @endif
                            @else
                                {{ $run->stage }}
                            @endif
                        </div>
                    @elseif ($run->status === \App\Models\AiRun::DONE)
                        <div style="font-size:.85rem;opacity:.8">
                            {{ $run->stage }}
                            @if ($u = $run->usage)
                                — {{ $u['sections'] ?? 0 }} {{ ($u['sections'] ?? 0) === 1 ? 'قسم' : 'أقسام' }}
                                @isset($u['model'])
                                    · <span dir="ltr">{{ $u['model'] }}</span>
                                @endisset
                                @if (! empty($u['free']))
                                    · مجاني (الطبقة المجانية)
                                @elseif (isset($u['cost_usd']))
                                    · التكلفة ≈ <span dir="ltr">${{ number_format($u['cost_usd'], 2) }}</span>
                                @endif
                                · {{ $run->started_at?->diffInSeconds($run->finished_at) ? (int) $run->started_at->diffInSeconds($run->finished_at).' ث' : '' }}
                            @endif
                            @if ($justFinished)
                                — اضغط "عرض النتيجة" لتحديث الصفحة.
                            @endif
                        </div>
                        @foreach (($run->usage['warnings'] ?? []) as $warning)
                            <div style="font-size:.8rem;color:var(--warning-700)">صورة لم تُضف — {{ $warning }}</div>
                        @endforeach
                    @else
                        <div style="font-size:.85rem;color:var(--danger-700)">{{ $run->error }}</div>
                    @endif
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-widgets::widget>
