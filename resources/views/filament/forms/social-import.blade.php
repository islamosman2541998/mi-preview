<div style="display:grid;gap:10px;padding:14px;border-radius:10px;border:1px dashed rgb(127 127 127 / .35)">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
        <x-filament::button
            type="button"
            icon="heroicon-o-arrow-down-tray"
            wire:click="importSocialProfile"
            wire:loading.attr="disabled"
            wire:target="importSocialProfile"
        >
            استيراد بيانات الحساب
        </x-filament::button>
        <span wire:loading wire:target="importSocialProfile" style="font-size:.85rem;opacity:.75">
            جارٍ قراءة البيانات والصور من Meta…
        </span>
    </div>
    <p style="font-size:.82rem;opacity:.72">
        تُملأ البيانات في الخطوات التالية لتراجعها قبل إنشاء الموقع. يعمل الاستيراد عبر Meta Graph API للحسابات والصفحات المسموح للتطبيق بقراءتها.
    </p>
</div>
