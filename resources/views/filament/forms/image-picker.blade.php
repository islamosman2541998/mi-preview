{{-- Image picker modal body. Behaviour: window.miImagePicker (resources/js/admin.js). --}}
@once
    <style>
        .mip { display: grid; gap: 14px; }
        .mip-tabs { display: flex; gap: 4px; border-bottom: 1px solid rgb(0 0 0 / .08); }
        .dark .mip-tabs { border-color: rgb(255 255 255 / .1); }
        .mip-tab { padding: 8px 14px; font-size: .875rem; font-weight: 500; opacity: .65; border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .mip-tab[aria-selected="true"] { opacity: 1; border-color: var(--primary-500); color: var(--primary-600); }
        .mip-bar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .mip-input, .mip-select { height: 38px; border-radius: 8px; border: 1px solid rgb(0 0 0 / .15); padding: 0 12px; font-size: .875rem; background: transparent; color: inherit; }
        .dark .mip-input, .dark .mip-select { border-color: rgb(255 255 255 / .15); }
        .mip-input { flex: 1 1 260px; direction: ltr; text-align: left; }
        .mip-select option { color: #111; }
        .mip-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .mip-chip { font-size: .78rem; padding: 3px 10px; border-radius: 999px; border: 1px solid rgb(0 0 0 / .12); direction: ltr; }
        .dark .mip-chip { border-color: rgb(255 255 255 / .15); }
        .mip-chip:hover { border-color: var(--primary-500); }
        .mip-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px; max-height: 56vh; overflow-y: auto; padding: 2px; }
        .mip-item { position: relative; aspect-ratio: 4 / 3; border-radius: 8px; overflow: hidden; background: var(--gray-100); outline-offset: 2px; }
        .dark .mip-item { background: rgb(255 255 255 / .05); }
        .mip-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .mip-item[aria-pressed="true"] { outline: 3px solid var(--primary-500); }
        .mip-num { position: absolute; top: 6px; inset-inline-start: 6px; min-width: 22px; height: 22px; border-radius: 11px; background: var(--primary-600); color: #fff; font-size: .75rem; font-weight: 700; display: grid; place-items: center; padding: 0 6px; }
        .mip-by { position: absolute; inset-inline: 0; bottom: 0; padding: 14px 8px 5px; font-size: .7rem; color: #fff; background: linear-gradient(transparent, rgb(0 0 0 / .65)); direction: ltr; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; opacity: 0; transition: opacity .15s; }
        .mip-item:hover .mip-by, .mip-item:focus-visible .mip-by { opacity: 1; }
        .mip-note { font-size: .8rem; opacity: .7; }
        .mip-empty { padding: 28px 12px; text-align: center; font-size: .875rem; opacity: .75; }
        .mip-error { padding: 10px 12px; border-radius: 8px; font-size: .85rem; background: rgb(220 38 38 / .08); color: rgb(185 28 28); }
        .dark .mip-error { color: rgb(252 165 165); }
        .mip-foot { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
    </style>
@endonce

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
<div
    class="mip"
    x-data="miImagePicker({
        state: $wire.$entangle(@js($getStatePath())),
        multiple: @js($multiple),
        withLibrary: @js($withLibrary),
        providers: @js($providers),
        libraryUrl: @js($libraryUrl),
        keywords: @js($keywords),
    })"
>
    @if (count($providers) + ($withLibrary ? 1 : 0) > 1)
        <div class="mip-tabs" role="tablist">
            @foreach ($providers as $provider)
                <button type="button" class="mip-tab" role="tab" :aria-selected="tab === @js($provider['key'])" x-on:click="openProvider(@js($provider['key']))">{{ $provider['label'] }}</button>
            @endforeach
            @if ($withLibrary)
                <button type="button" class="mip-tab" role="tab" :aria-selected="tab === 'library'" x-on:click="openLibrary()">مكتبة الموقع</button>
            @endif
        </div>
    @endif

    {{-- stock photos (one provider at a time) --}}
    @if ($providers)
        <div x-show="tab !== 'library'" class="mip">
            <form class="mip-bar" x-on:submit.prevent="search()">
                <input type="search" class="mip-input" x-model="query" placeholder="construction site, clinic interior…" aria-label="بحث">
                <select class="mip-select" x-model="orientation" x-on:change="search()" aria-label="اتجاه الصورة">
                    <option value="landscape">أفقية</option>
                    <option value="portrait">رأسية</option>
                    <option value="square">مربعة</option>
                    <option value="">الكل</option>
                </select>
                <x-filament::button type="submit" size="sm">بحث</x-filament::button>
            </form>

            <template x-if="keywords.length">
                <div class="mip-chips">
                    <template x-for="k in keywords" :key="k">
                        <button type="button" class="mip-chip" x-text="k" x-on:click="query = k; search()"></button>
                    </template>
                </div>
            </template>

            <template x-if="error"><div class="mip-error" x-text="error"></div></template>

            <div class="mip-grid" x-show="photos.length">
                <template x-for="p in photos" :key="tab + p.id">
                    <button type="button" class="mip-item" :aria-pressed="isPicked(tab, p.id)" :style="p.avg_color ? 'background:' + p.avg_color : ''"
                        x-on:click="toggle(tab, p.id)" :title="p.alt">
                        <img :src="p.thumb" :alt="p.alt" loading="lazy">
                        <template x-if="isPicked(tab, p.id)"><span class="mip-num" x-text="multiple ? order(tab, p.id) : '✓'"></span></template>
                        <span class="mip-by" x-text="p.photographer"></span>
                    </button>
                </template>
            </div>

            <template x-if="!loading && searched && !photos.length && !error">
                <div class="mip-empty">لا توجد نتائج — جرّب كلمات إنجليزية أخرى.</div>
            </template>

            <div class="mip-foot">
                <span class="mip-note" x-text="provider()?.attribution"></span>
                <x-filament::button color="gray" size="sm" x-show="hasMore" x-on:click="more()" ::disabled="loading">عرض المزيد</x-filament::button>
            </div>
        </div>
    @else
        <div class="mip-empty" x-show="tab !== 'library'" @if ($withLibrary) x-cloak @endif>{{ $setupHint }}</div>
    @endif

    {{-- site library --}}
    @if ($withLibrary)
        <div x-show="tab === 'library'" x-cloak class="mip">
            @unless ($providers)
                <div class="mip-note">{{ $setupHint }}</div>
            @endunless
            <div class="mip-grid" x-show="library.length">
                <template x-for="m in library" :key="'m' + m.id">
                    <button type="button" class="mip-item" :aria-pressed="isPicked('media', m.id)" x-on:click="toggle('media', m.id)" :title="m.credit || m.alt || ''">
                        <img :src="m.thumb" :alt="m.alt || ''" loading="lazy">
                        <template x-if="isPicked('media', m.id)"><span class="mip-num" x-text="multiple ? order('media', m.id) : '✓'"></span></template>
                    </button>
                </template>
            </div>
            <template x-if="libraryLoaded && !library.length">
                <div class="mip-empty">مكتبة الموقع فارغة — الصور التي ترفعها أو تختارها من بنك الصور تظهر هنا.</div>
            </template>
        </div>
    @endif

    <div class="mip-note" x-show="picked.length" x-text="multiple ? ('تم تحديد ' + picked.length + ' — بالترتيب المعروض على الصور') : 'تم تحديد صورة'"></div>
    <div class="mip-note" x-show="loading">جارٍ التحميل…</div>
</div>
</x-dynamic-component>
