<?php

namespace App\Filament\Support;

use App\Models\Site;
use App\Services\Stock\StockPhotos;
use App\Services\Stock\StockProvider;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * "Pick an image" for any FileUpload field: stock photo search (Pixabay / Pexels) + the
 * site's own library in one modal. Stock picks are downloaded into the site's media folder first, then
 * the field's state is set to the stored path(s), which the upload widget re-renders.
 */
class ImagePicker
{
    /** Attach the picker to an image field (kept in the label row, out of the way). */
    public static function attach(FileUpload $field, int|string|Closure|null $siteId, bool $multiple = false): FileUpload
    {
        return self::resizeOnUpload($field)->afterLabel(self::fieldAction($siteId, $multiple));
    }

    /** Shrink big photos in the admin's browser before upload (no image work on the server). */
    public static function resizeOnUpload(FileUpload $field): FileUpload
    {
        $max = (string) config('preview.media.max_width');

        return $field
            ->automaticallyResizeImagesMode('contain')
            ->automaticallyResizeImagesToWidth($max)
            ->automaticallyResizeImagesToHeight($max)
            ->automaticallyUpscaleImagesWhenResizing(false);
    }

    public static function fieldAction(int|string|Closure|null $siteId, bool $multiple = false): Action
    {
        return self::base('pickImage', $siteId, $multiple, withLibrary: true)
            ->label($multiple ? 'إضافة صور من بنك الصور أو المكتبة' : 'اختيار من بنك الصور أو المكتبة')
            ->link()
            ->size('sm')
            ->action(function (array $data, FileUpload $component) use ($siteId, $multiple) {
                $paths = self::resolve(self::site($siteId), $data['picked'] ?? []);

                $state = $multiple ? array_filter((array) $component->getState(), 'is_string') : [];
                foreach ($paths as $path) {
                    $state[(string) Str::uuid()] = $path;
                }

                $component->state($state);
            });
    }

    /** Header action for the media library: import stock photos without placing them anywhere. */
    public static function libraryAction(Closure $siteId): Action
    {
        return self::base('importStock', $siteId, multiple: true, withLibrary: false)
            ->label('إضافة من بنك الصور')
            ->color('gray')
            ->action(fn (array $data) => self::resolve(self::site($siteId), $data['picked'] ?? []));
    }

    private static function base(string $name, int|string|Closure|null $siteId, bool $multiple, bool $withLibrary): Action
    {
        return Action::make($name)
            ->icon('heroicon-o-photo')
            ->modalHeading('اختيار صورة')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitActionLabel('استخدام المحدد')
            ->schema(fn () => [
                ViewField::make('picked')
                    ->hiddenLabel()
                    ->view('filament.forms.image-picker')
                    ->viewData(self::viewData(self::site($siteId), $multiple, $withLibrary))
                    ->default([])
                    ->required()
                    ->validationMessages(['required' => 'اختر صورة واحدة على الأقل.']),
            ]);
    }

    /** @return list<string> stored paths, in pick order */
    public static function resolve(Site $site, array $picked): array
    {
        $paths = [];
        $stock = app(StockPhotos::class);

        try {
            foreach (array_slice($picked, 0, 24) as $pick) {
                $id = (int) ($pick['id'] ?? 0);

                $source = (string) ($pick['source'] ?? '');

                $paths[] = match (true) {
                    $source === 'media' => $site->media()->find($id)?->path ?? throw new RuntimeException('الصورة غير موجودة في مكتبة هذا الموقع.'),
                    $stock->has($source) => $stock->get($source)->import($site, $id)->path,
                    default => throw new RuntimeException('اختيار غير صالح.'),
                };
            }
        } catch (RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            throw new Halt;
        }

        if ($paths) {
            Notification::make()->title(count($paths) === 1 ? 'تمت إضافة الصورة' : 'تمت إضافة '.count($paths).' صور')->success()->send();
        }

        return $paths;
    }

    private static function viewData(Site $site, bool $multiple, bool $withLibrary): array
    {
        $keywords = array_values(array_filter((array) ($site->industry?->stock_keywords ?? [])));

        return [
            'multiple' => $multiple,
            'withLibrary' => $withLibrary,
            'providers' => array_values(array_map(fn (StockProvider $p) => [
                'key' => $p->key(),
                'label' => $p->label(),
                'url' => route('admin.picker.stock', $p->key()),
                'attribution' => $p->attribution(),
            ], app(StockPhotos::class)->configured())),
            'setupHint' => new HtmlString('لتفعيل البحث في بنوك الصور المجانية أضف <bdi dir="ltr">PIXABAY_API_KEY</bdi> في ملف <bdi dir="ltr">.env</bdi> (مفتاح مجاني فوري من <bdi dir="ltr">pixabay.com/api/docs</bdi>).'),
            'libraryUrl' => route('admin.picker.library', $site),
            'keywords' => $keywords,
        ];
    }

    private static function site(int|string|Closure|null $siteId): Site
    {
        $id = $siteId instanceof Closure ? $siteId() : $siteId;

        return Site::with('industry')->findOrFail($id);
    }
}
