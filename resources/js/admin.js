/**
 * Admin-only helpers for the Filament panel (loaded by a render hook, see AdminPanelProvider).
 *
 *  - miLogoTools:   in-browser logo background removal (@imgly/background-removal, AGPL — admin-only,
 *                   never shipped to preview sites), transparent-edge trimming, palette extraction.
 *  - miImagePicker: stock photo search (Pixabay / Pexels) + site library grid in the "pick image" modal.
 *
 * The background-removal library is imported from a pinned CDN build only when the button is
 * pressed — it fetches its ONNX runtime and ~40 MB model from IMG.LY's CDN anyway (cached by the
 * browser), so bundling it would only add a 24 MB unused wasm file to public/build.
 */

const BG_LIBRARY = 'https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.7.0/+esm';
const BG_MODEL = 'isnet_fp16';

async function removeBackground(blob, onProgress) {
    const { removeBackground: run } = await import(/* @vite-ignore */ BG_LIBRARY);

    return run(blob, {
        model: BG_MODEL,
        output: { format: 'image/png', quality: 1 },
        progress: (key, current, total) => onProgress?.(key, current, total),
    });
}

/** Crop fully transparent margins (with a little breathing room) and cap the size. */
async function trimTransparent(blob, maxSide = 1000) {
    const bitmap = await createImageBitmap(blob);
    const { width, height } = bitmap;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(bitmap, 0, 0);

    const data = ctx.getImageData(0, 0, width, height).data;
    let top = height, left = width, right = -1, bottom = -1;

    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            if (data[(y * width + x) * 4 + 3] > 8) {
                if (x < left) left = x;
                if (x > right) right = x;
                if (y < top) top = y;
                if (y > bottom) bottom = y;
            }
        }
    }

    if (right < 0) {
        throw new Error('النتيجة فارغة — يبدو أن النموذج اعتبر الشعار كله خلفية.');
    }

    const pad = Math.round(Math.max(right - left, bottom - top) * 0.03);
    left = Math.max(0, left - pad);
    top = Math.max(0, top - pad);
    right = Math.min(width - 1, right + pad);
    bottom = Math.min(height - 1, bottom + pad);

    const w = right - left + 1;
    const h = bottom - top + 1;
    const scale = Math.min(1, maxSide / Math.max(w, h));

    const out = document.createElement('canvas');
    out.width = Math.round(w * scale);
    out.height = Math.round(h * scale);
    out.getContext('2d').drawImage(canvas, left, top, w, h, 0, 0, out.width, out.height);

    return new Promise((resolve, reject) => out.toBlob((b) => (b ? resolve(b) : reject(new Error('toBlob failed'))), 'image/png'));
}

window.miLogoTools = ({ remoteEnabled }) => ({
    remoteEnabled,
    busy: false,
    status: '',
    progress: null,
    failed: false,

    start(text) {
        this.busy = true;
        this.failed = false;
        this.status = text;
        this.progress = null;
    },

    finish(text = '') {
        this.busy = false;
        this.progress = null;
        this.status = text;
    },

    fail(text) {
        this.busy = false;
        this.failed = true;
        this.progress = null;
        this.status = text;
    },

    async removeInBrowser() {
        this.start('جارٍ تجهيز الشعار…');

        try {
            const src = await this.$wire.logoSource();
            if (!src) {
                return this.fail('ارفع الشعار أولًا.');
            }

            const blob = await (await fetch(src)).blob();
            if (blob.type === 'image/svg+xml') {
                return this.fail('الشعار SVG — غالبًا شفاف بالفعل، ارفعه مباشرة في خانة "الشعار بدون خلفية".');
            }

            this.status = 'جارٍ تحميل نموذج إزالة الخلفية (أول مرة فقط)…';
            this.progress = 0;

            const result = await removeBackground(blob, (key, current, total) => {
                if (!total) return;
                if (key.startsWith('compute')) {
                    this.status = 'جارٍ إزالة الخلفية…';
                }
                this.progress = Math.min(100, Math.round((current / total) * 100));
            });

            this.status = 'جارٍ الحفظ…';
            this.progress = null;

            const trimmed = await trimTransparent(result);
            const file = new File([trimmed], 'logo-nobg.png', { type: 'image/png' });

            await new Promise((resolve, reject) => this.$wire.upload('logoNobgUpload', file, resolve, reject));
            await this.$wire.applyLogoNobg();

            this.finish('تم — راجع النتيجة، ولو مش مظبوطة ارفع نسخة PNG شفافة يدويًا.');
        } catch (e) {
            console.error(e);
            this.fail('تعذّرت إزالة الخلفية: ' + (e?.message || e));
        }
    },

    async removeRemote() {
        this.start('جارٍ الإرسال إلى remove.bg…');
        try {
            await this.$wire.removeLogoBgRemote();
            this.finish();
        } catch (e) {
            this.fail('تعذّر الاتصال بالخادم.');
        }
    },

    async extractPalette() {
        this.start('جارٍ استخراج الألوان…');
        try {
            await this.$wire.extractLogoPalette();
            this.finish();
        } catch (e) {
            this.fail('تعذّر الاتصال بالخادم.');
        }
    },
});

window.miImagePicker = (cfg) => ({
    state: cfg.state,
    multiple: cfg.multiple,
    withLibrary: cfg.withLibrary,
    providers: cfg.providers || [],
    keywords: cfg.keywords || [],

    tab: (cfg.providers || [])[0]?.key || 'library',
    query: (cfg.keywords || [])[0] || '',
    orientation: 'landscape',
    photos: [],
    page: 1,
    hasMore: false,
    loading: false,
    searched: false,
    error: '',
    seq: 0,

    library: [],
    libraryLoaded: false,

    init() {
        if (!Array.isArray(this.state)) {
            this.state = [];
        }

        if (this.tab === 'library') {
            if (this.withLibrary) this.openLibrary();
        } else if (this.query) {
            this.search();
        }
    },

    provider() {
        return this.providers.find((p) => p.key === this.tab);
    },

    openProvider(key) {
        if (this.tab === key) return;
        this.tab = key;
        this.search();
    },

    get picked() {
        return Array.isArray(this.state) ? this.state : [];
    },

    indexOf(source, id) {
        return this.picked.findIndex((p) => p.source === source && p.id === id);
    },

    isPicked(source, id) {
        return this.indexOf(source, id) !== -1;
    },

    order(source, id) {
        return this.indexOf(source, id) + 1;
    },

    toggle(source, id) {
        const i = this.indexOf(source, id);

        if (i !== -1) {
            this.state = this.picked.filter((_, j) => j !== i);
        } else {
            this.state = this.multiple ? [...this.picked, { source, id }] : [{ source, id }];
        }
    },

    async getJson(url) {
        const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const json = await res.json().catch(() => ({}));

        if (!res.ok) {
            throw new Error(json.message || 'حدث خطأ (' + res.status + ')');
        }

        return json;
    },

    async search() {
        this.page = 1;
        this.photos = [];
        await this.fetchPage();
    },

    async more() {
        this.page++;
        await this.fetchPage();
    },

    async fetchPage() {
        const q = this.query.trim();
        const provider = this.provider();
        if (!q || !provider) return;
        const seq = ++this.seq; // newer searches (or a tab switch) win

        const url = new URL(provider.url, location.href);
        url.searchParams.set('q', q);
        url.searchParams.set('page', this.page);
        if (this.orientation) url.searchParams.set('orientation', this.orientation);

        this.loading = true;
        this.error = '';

        try {
            const json = await this.getJson(url);
            if (seq !== this.seq) return;
            this.photos = this.page === 1 ? json.photos : [...this.photos, ...json.photos];
            this.hasMore = !!json.has_more;
        } catch (e) {
            if (seq !== this.seq) return;
            this.error = e.message;
            this.hasMore = false;
        } finally {
            if (seq === this.seq) {
                this.loading = false;
                this.searched = true;
            }
        }
    },

    async openLibrary() {
        this.tab = 'library';
        if (this.libraryLoaded) return;

        this.loading = true;
        try {
            this.library = (await this.getJson(cfg.libraryUrl)).media;
        } catch (e) {
            this.library = [];
        } finally {
            this.loading = false;
            this.libraryLoaded = true;
        }
    },
});
