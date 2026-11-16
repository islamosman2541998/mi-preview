<?php

namespace App\Filament\Resources\Sites\Concerns;

use App\Models\Industry;
use App\Services\LogoPalette;
use App\Services\RemoveBg;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

/**
 * Livewire methods behind the logo tools (create wizard + edit page), see
 * resources/views/filament/forms/logo-tools.blade.php.
 *
 * Results are stored on the public disk right away and the upload field is set
 * to the stored path: Filament's upload widget re-renders for stored paths
 * (it deliberately ignores state that still holds temporary uploads).
 */
trait HandlesLogoTools
{
    /** Background-removed PNG uploaded by the browser (see miLogoTools). */
    public $logoNobgUpload = null;

    /** The current logo as a data URI, for in-browser processing. */
    public function logoSource(): ?string
    {
        $path = $this->logoFilePath('logo_path');

        if (! $path) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    public function applyLogoNobg(): void
    {
        $file = $this->logoNobgUpload;

        $valid = $file instanceof TemporaryUploadedFile && Validator::make(
            ['file' => $file],
            ['file' => ['image', 'mimes:png,webp', 'max:4096']],
        )->passes();

        if (! $valid) {
            $this->reset('logoNobgUpload');
            Notification::make()->title('تعذّر حفظ الشعار بدون خلفية')->danger()->send();

            return;
        }

        $path = $file->storeAs('sites/logos', Str::uuid().'.png', 'public');
        $file->delete();
        $this->reset('logoNobgUpload');

        $this->setLogoNobg($path);
        Notification::make()->title('تمت إزالة الخلفية')->body('راجع النتيجة في خانة "الشعار بدون خلفية".')->success()->send();
    }

    public function removeLogoBgRemote(): void
    {
        $source = $this->logoFilePath('logo_path');

        if (! $source) {
            Notification::make()->title('ارفع الشعار أولًا')->warning()->send();

            return;
        }

        try {
            $png = app(RemoveBg::class)->remove($source);
        } catch (RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        $path = 'sites/logos/'.Str::uuid().'.png';
        Storage::disk('public')->put($path, $png, 'public');

        $this->setLogoNobg($path);
        Notification::make()->title('تمت إزالة الخلفية عبر remove.bg')->success()->send();
    }

    public function extractLogoPalette(bool $quiet = false): void
    {
        $path = $this->logoFilePath('logo_path') ?? $this->logoFilePath('logo_nobg_path');

        if (! $path) {
            $quiet || Notification::make()->title('ارفع الشعار أولًا')->warning()->send();

            return;
        }

        $fallback = Industry::find($this->data['industry_id'] ?? null)?->default_palette;

        try {
            $result = app(LogoPalette::class)->fromFile($path, $fallback);
        } catch (Throwable) {
            Notification::make()->title('تعذّر قراءة ألوان الشعار')->body('جرّب صورة PNG أو JPG.')->warning()->send();

            return;
        }

        $this->data['palette'] = array_merge((array) ($this->data['palette'] ?? []), $result['palette']);

        $result['monochrome']
            ? Notification::make()->title('الشعار بدون ألوان واضحة')->body('تم استخدام ألوان النشاط — يمكنك تعديلها يدويًا.')->info()->send()
            : Notification::make()->title('تم استخراج الألوان من الشعار')->body('راجع التباين بالأسفل وعدّل لو احتجت.')->success()->send();
    }

    protected function setLogoNobg(string $path): void
    {
        $this->data['logo_nobg_path'] = [(string) Str::uuid() => $path];
    }

    /** Absolute local path of the file currently in a logo field (temporary or stored). */
    protected function logoFilePath(string $field): ?string
    {
        foreach ((array) ($this->data[$field] ?? []) as $file) {
            if (is_string($file) && str_starts_with($file, 'livewire-file:')) {
                $file = TemporaryUploadedFile::unserializeFromLivewireRequest($file);
            }

            if ($file instanceof TemporaryUploadedFile && $file->exists()) {
                return $file->getRealPath();
            }

            if (is_string($file) && $file !== '' && Storage::disk('public')->exists($file)) {
                return Storage::disk('public')->path($file);
            }
        }

        return null;
    }
}
