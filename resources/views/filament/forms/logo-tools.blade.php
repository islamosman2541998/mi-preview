{{-- Logo helpers under the two logo uploads. Behaviour: window.miLogoTools (resources/js/admin.js). --}}
<div
    x-data="miLogoTools({ remoteEnabled: @js($remoteEnabled) })"
    style="display:grid;gap:10px;padding:12px 14px;border-radius:10px;border:1px dashed rgb(127 127 127 / .35)"
>
    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
        <x-filament::button size="sm" icon="heroicon-o-scissors" x-on:click="removeInBrowser()" ::disabled="busy">
            إزالة الخلفية تلقائيًا
        </x-filament::button>

        @if ($remoteEnabled)
            <x-filament::button size="sm" color="gray" x-on:click="removeRemote()" ::disabled="busy">
                إزالة بـ remove.bg
            </x-filament::button>
        @endif

        <x-filament::button size="sm" color="gray" icon="heroicon-o-swatch" x-on:click="extractPalette()" ::disabled="busy">
            استخراج الألوان من الشعار
        </x-filament::button>
    </div>

    <div style="font-size:.8rem;opacity:.75" x-show="!status">
        إزالة الخلفية تتم داخل المتصفح (أول مرة تحمّل نموذجًا ~40 ميجا ثم تُخزَّن)، والنتيجة تُقصّ تلقائيًا من الأطراف الفارغة.
        استخراج الألوان يملأ الألوان بالأسفل ويمكنك تعديلها بعده.
    </div>

    <div x-show="status" x-cloak style="display:grid;gap:6px;font-size:.85rem">
        <span x-text="status" :style="failed ? 'color:var(--danger-600)' : ''"></span>
        <span x-show="progress !== null" style="height:6px;border-radius:3px;background:var(--gray-200);overflow:hidden">
            <span style="display:block;height:100%;background:var(--primary-500);transition:width .2s" :style="'width:' + (progress ?? 0) + '%'"></span>
        </span>
    </div>
</div>
