<?php

namespace App\Filament\Support;

use App\Enums\SectionType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Per-type section editing.
 *
 * Stored content:  { ar: {...}, en: {...}, shared: {...} }  — arrays under ar/en/shared are index-aligned.
 * Form state ("c"): text fields per locale in c.ar / c.en, shared fields in c.shared, and every list
 * as ONE repeater whose rows hold both languages side by side (title_ar, title_en, image…), so the
 * index alignment can never drift while editing. toForm()/fromForm() convert between the two.
 */
class SectionFields
{
    // field kinds
    private const TEXT = 'text';

    private const AREA = 'textarea';

    private const IMAGE = 'image';

    private const IMAGES = 'images';

    private const NUMBER = 'number';

    private const URL = 'url';

    /**
     * text   → per-locale scalar fields
     * shared → locale-independent scalar fields
     * items  → [key, label, text fields, shared fields]   (rows aligned across ar/en/shared)
     * lists  → [key => label]                             (plain string lists per locale)
     * logos  → shared-only rows (name, image)
     * menu   → categories → dishes (+ prices)
     */
    public static function definition(SectionType $type): array
    {
        $title = ['title' => [self::TEXT, 'العنوان']];

        return match ($type) {
            SectionType::Hero => [
                'text' => [
                    'eyebrow' => [self::TEXT, 'سطر تعريفي فوق العنوان'],
                    'title' => [self::TEXT, 'العنوان الرئيسي'],
                    'subtitle' => [self::AREA, 'الوصف'],
                    'primary_cta' => [self::TEXT, 'نص الزر الرئيسي (واتساب)'],
                    'secondary_cta' => [self::TEXT, 'نص الزر الثانوي'],
                ],
                'shared' => [
                    'image' => [self::IMAGE, 'صورة الخلفية'],
                    'secondary_target' => [self::TEXT, 'رابط الزر الثانوي', 'مثال: /projects أو #services'],
                ],
            ],
            SectionType::About => [
                'text' => $title + ['body' => [self::AREA, 'النص']],
                'shared' => ['image' => [self::IMAGE, 'صورة'], 'since' => [self::NUMBER, 'سنة التأسيس']],
                'lists' => ['points' => 'نقاط مميزة'],
            ],
            SectionType::Services => [
                'text' => $title + ['intro' => [self::AREA, 'مقدمة']],
                'items' => ['items', 'الخدمات', ['title' => [self::TEXT, 'اسم الخدمة'], 'description' => [self::AREA, 'الوصف']], ['image' => [self::IMAGE, 'صورة (اختياري)']]],
            ],
            SectionType::Projects => [
                'text' => $title + ['intro' => [self::AREA, 'مقدمة']],
                'items' => ['items', 'المشاريع', [
                    'title' => [self::TEXT, 'اسم المشروع'],
                    'location' => [self::TEXT, 'الموقع'],
                    'category' => [self::TEXT, 'النوع'],
                    'description' => [self::AREA, 'الوصف'],
                ], ['image' => [self::IMAGE, 'صورة'], 'year' => [self::NUMBER, 'سنة التنفيذ']]],
            ],
            SectionType::Gallery => [
                'text' => $title,
                'shared' => ['images' => [self::IMAGES, 'الصور']],
            ],
            SectionType::Stats => [
                'text' => $title,
                'items' => ['items', 'الأرقام', ['label' => [self::TEXT, 'الوصف']], ['value' => [self::NUMBER, 'الرقم'], 'suffix' => [self::TEXT, 'لاحقة (+، %)']]],
            ],
            SectionType::Testimonials => [
                'text' => $title,
                'items' => ['items', 'الآراء', ['quote' => [self::AREA, 'الرأي'], 'name' => [self::TEXT, 'الاسم'], 'role' => [self::TEXT, 'الصفة']], []],
            ],
            SectionType::Clients => [
                'text' => $title,
                'logos' => true,
            ],
            SectionType::Faq => [
                'text' => $title + ['intro' => [self::AREA, 'مقدمة']],
                'items' => ['items', 'الأسئلة', ['q' => [self::TEXT, 'السؤال'], 'a' => [self::AREA, 'الإجابة']], []],
            ],
            SectionType::Cta => [
                'text' => $title + ['body' => [self::AREA, 'النص'], 'button' => [self::TEXT, 'نص الزر']],
            ],
            SectionType::Contact => [
                'text' => $title + ['body' => [self::AREA, 'النص'], 'hours' => [self::TEXT, 'مواعيد العمل']],
            ],
            SectionType::Map => [
                'text' => $title,
                'shared' => ['embed_url' => [self::URL, 'رابط تضمين الخريطة', 'من Google Maps: مشاركة ← تضمين خريطة ← انسخ رابط src']],
            ],
            SectionType::Menu => [
                'text' => $title,
                'menu' => true,
            ],
            SectionType::Booking => [
                'text' => $title + ['body' => [self::AREA, 'النص'], 'button' => [self::TEXT, 'نص الزر']],
                'lists' => ['options' => 'الخدمات المتاحة للحجز'],
            ],
            SectionType::Products => [
                'text' => $title + ['intro' => [self::AREA, 'مقدمة']],
                'items' => ['items', 'المنتجات', ['name' => [self::TEXT, 'اسم المنتج'], 'description' => [self::AREA, 'الوصف']], ['image' => [self::IMAGE, 'صورة'], 'price' => [self::NUMBER, 'السعر']]],
            ],
            SectionType::Certifications => [
                'text' => $title + ['intro' => [self::AREA, 'مقدمة']],
                'items' => ['items', 'الشهادات', ['name' => [self::TEXT, 'الشهادة'], 'issuer' => [self::TEXT, 'جهة الإصدار']], ['image' => [self::IMAGE, 'صورة (اختياري)']]],
            ],
        };
    }

    // ===================================================================== mapping

    public static function toForm(SectionType $type, ?array $content): array
    {
        $def = self::definition($type);
        $content ??= [];
        $ar = $content['ar'] ?? [];
        $en = $content['en'] ?? [];
        $shared = $content['shared'] ?? [];
        $form = ['ar' => [], 'en' => [], 'shared' => []];

        foreach (array_keys($def['text'] ?? []) as $f) {
            $form['ar'][$f] = $ar[$f] ?? null;
            $form['en'][$f] = $en[$f] ?? null;
        }

        foreach (array_keys($def['shared'] ?? []) as $f) {
            $form['shared'][$f] = $shared[$f] ?? ($def['shared'][$f][0] === self::IMAGES ? [] : null);
        }

        if ($items = $def['items'] ?? null) {
            [$key, , $text, $sharedFields] = $items;
            $count = max(count($ar[$key] ?? []), count($en[$key] ?? []), count($shared[$key] ?? []));
            $form['items'] = [];
            for ($i = 0; $i < $count; $i++) {
                $row = [];
                foreach (array_keys($text) as $f) {
                    $row[$f.'_ar'] = $ar[$key][$i][$f] ?? null;
                    $row[$f.'_en'] = $en[$key][$i][$f] ?? null;
                }
                foreach (array_keys($sharedFields) as $f) {
                    $row[$f] = $shared[$key][$i][$f] ?? null;
                }
                $form['items'][] = $row;
            }
        }

        foreach (array_keys($def['lists'] ?? []) as $key) {
            $count = max(count($ar[$key] ?? []), count($en[$key] ?? []));
            $form['lists'][$key] = [];
            for ($i = 0; $i < $count; $i++) {
                $form['lists'][$key][] = ['ar' => $ar[$key][$i] ?? null, 'en' => $en[$key][$i] ?? null];
            }
        }

        if (! empty($def['logos'])) {
            $form['logos'] = array_values(array_map(
                fn ($l) => ['name' => $l['name'] ?? null, 'image' => $l['image'] ?? null],
                $shared['logos'] ?? [],
            ));
        }

        if (! empty($def['menu'])) {
            $form['menu'] = [];
            $categories = max(count($ar['categories'] ?? []), count($en['categories'] ?? []));
            for ($ci = 0; $ci < $categories; $ci++) {
                $catAr = $ar['categories'][$ci] ?? [];
                $catEn = $en['categories'][$ci] ?? [];
                $dishes = [];
                $n = max(count($catAr['items'] ?? []), count($catEn['items'] ?? []));
                for ($ii = 0; $ii < $n; $ii++) {
                    $dishes[] = [
                        'name_ar' => $catAr['items'][$ii]['name'] ?? null,
                        'name_en' => $catEn['items'][$ii]['name'] ?? null,
                        'description_ar' => $catAr['items'][$ii]['description'] ?? null,
                        'description_en' => $catEn['items'][$ii]['description'] ?? null,
                        'price' => $shared['prices'][$ci][$ii] ?? null,
                    ];
                }
                $form['menu'][] = ['name_ar' => $catAr['name'] ?? null, 'name_en' => $catEn['name'] ?? null, 'dishes' => $dishes];
            }
        }

        return $form;
    }

    public static function fromForm(SectionType $type, ?array $form): array
    {
        $def = self::definition($type);
        $form ??= [];
        $content = ['ar' => [], 'en' => [], 'shared' => []];

        foreach (array_keys($def['text'] ?? []) as $f) {
            $content['ar'][$f] = self::clean($form['ar'][$f] ?? null);
            $content['en'][$f] = self::clean($form['en'][$f] ?? null);
        }

        foreach ($def['shared'] ?? [] as $f => $spec) {
            $value = $form['shared'][$f] ?? null;
            $content['shared'][$f] = match ($spec[0]) {
                self::IMAGES => array_values(array_filter((array) $value)),
                self::NUMBER => is_numeric($value) ? $value + 0 : null,
                default => self::clean($value),
            };
        }

        if ($items = $def['items'] ?? null) {
            [$key, , $text, $sharedFields] = $items;
            foreach (array_values($form['items'] ?? []) as $row) {
                $rowAr = $rowEn = $rowShared = [];
                foreach (array_keys($text) as $f) {
                    $rowAr[$f] = self::clean($row[$f.'_ar'] ?? null);
                    $rowEn[$f] = self::clean($row[$f.'_en'] ?? null);
                }
                foreach ($sharedFields as $f => $spec) {
                    $v = $row[$f] ?? null;
                    $rowShared[$f] = $spec[0] === self::NUMBER ? (is_numeric($v) ? $v + 0 : null) : self::clean($v);
                }
                $content['ar'][$key][] = $rowAr;
                $content['en'][$key][] = $rowEn;
                if ($sharedFields) {
                    $content['shared'][$key][] = $rowShared;
                }
            }
            $content['ar'][$key] ??= [];
            $content['en'][$key] ??= [];
        }

        foreach (array_keys($def['lists'] ?? []) as $key) {
            $content['ar'][$key] = [];
            $content['en'][$key] = [];
            foreach (array_values($form['lists'][$key] ?? []) as $row) {
                $content['ar'][$key][] = self::clean($row['ar'] ?? null) ?? '';
                $content['en'][$key][] = self::clean($row['en'] ?? null) ?? '';
            }
        }

        if (! empty($def['logos'])) {
            $content['shared']['logos'] = array_values(array_map(
                fn ($r) => ['name' => self::clean($r['name'] ?? null), 'image' => self::clean($r['image'] ?? null)],
                $form['logos'] ?? [],
            ));
        }

        if (! empty($def['menu'])) {
            $content['ar']['categories'] = $content['en']['categories'] = $content['shared']['prices'] = [];
            foreach (array_values($form['menu'] ?? []) as $ci => $cat) {
                $itemsAr = $itemsEn = $prices = [];
                foreach (array_values($cat['dishes'] ?? []) as $dish) {
                    $itemsAr[] = ['name' => self::clean($dish['name_ar'] ?? null), 'description' => self::clean($dish['description_ar'] ?? null)];
                    $itemsEn[] = ['name' => self::clean($dish['name_en'] ?? null), 'description' => self::clean($dish['description_en'] ?? null)];
                    $prices[] = is_numeric($dish['price'] ?? null) ? $dish['price'] + 0 : null;
                }
                $content['ar']['categories'][$ci] = ['name' => self::clean($cat['name_ar'] ?? null), 'items' => $itemsAr];
                $content['en']['categories'][$ci] = ['name' => self::clean($cat['name_en'] ?? null), 'items' => $itemsEn];
                $content['shared']['prices'][$ci] = $prices;
            }
        }

        if ($content['shared'] === []) {
            unset($content['shared']);
        }

        return $content;
    }

    private static function clean(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        return $value;
    }

    // ====================================================================== schema

    /** Form components bound to the "c" state key, for a given type. */
    public static function components(?SectionType $type, int|string|null $siteId): array
    {
        if (! $type) {
            return [];
        }

        $def = self::definition($type);
        $dir = 'sites/'.($siteId ?: 'shared').'/sections';
        self::$siteId = $siteId ?: null;
        $tabs = [];

        foreach (['ar' => 'العربية', 'en' => 'English'] as $locale => $label) {
            $fields = [];
            foreach ($def['text'] ?? [] as $f => $spec) {
                $fields[] = self::field("c.{$locale}.{$f}", $spec, $dir, $locale);
            }
            $tabs[] = Tab::make($label)->schema($fields);
        }

        $extra = [];

        if ($shared = $def['shared'] ?? null) {
            foreach ($shared as $f => $spec) {
                $extra[] = self::field("c.shared.{$f}", $spec, $dir);
            }
        }

        if ($items = $def['items'] ?? null) {
            [, $label, $text, $sharedFields] = $items;
            $rowFields = [];
            foreach ($text as $f => $spec) {
                $rowFields[] = Grid::make(2)->schema([
                    self::field($f.'_ar', [$spec[0], $spec[1].' (عربي)'], $dir, 'ar'),
                    self::field($f.'_en', [$spec[0], $spec[1].' (EN)'], $dir, 'en'),
                ]);
            }
            if ($sharedFields) {
                $rowFields[] = Grid::make(count($sharedFields))->schema(
                    array_map(fn ($f) => self::field($f, $sharedFields[$f], $dir), array_keys($sharedFields))
                );
            }
            $first = array_key_first($text);
            $extra[] = Repeater::make('c.items')
                ->label($label)
                ->schema($rowFields)
                ->reorderable()
                ->collapsible()
                ->cloneable()
                ->itemLabel(fn (array $state) => $state[$first.'_ar'] ?? $state[$first.'_en'] ?? null)
                ->defaultItems(0)
                ->addActionLabel('إضافة');
        }

        foreach ($def['lists'] ?? [] as $key => $label) {
            $extra[] = Repeater::make("c.lists.{$key}")
                ->label($label)
                ->schema([Grid::make(2)->schema([
                    TextInput::make('ar')->label('عربي'),
                    TextInput::make('en')->label('English')->extraInputAttributes(['dir' => 'ltr']),
                ])])
                ->reorderable()
                ->defaultItems(0)
                ->addActionLabel('إضافة');
        }

        if (! empty($def['logos'])) {
            $extra[] = Repeater::make('c.logos')
                ->label('الشعارات')
                ->schema([Grid::make(2)->schema([
                    TextInput::make('name')->label('الاسم')->required(),
                    self::field('image', [self::IMAGE, 'الشعار (اختياري)'], $dir),
                ])])
                ->reorderable()
                ->itemLabel(fn (array $state) => $state['name'] ?? null)
                ->defaultItems(0)
                ->addActionLabel('إضافة');
        }

        if (! empty($def['menu'])) {
            $extra[] = Repeater::make('c.menu')
                ->label('أقسام المنيو')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name_ar')->label('اسم القسم (عربي)'),
                        TextInput::make('name_en')->label('اسم القسم (EN)')->extraInputAttributes(['dir' => 'ltr']),
                    ]),
                    Repeater::make('dishes')->label('الأصناف')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name_ar')->label('الصنف (عربي)'),
                            TextInput::make('name_en')->label('الصنف (EN)')->extraInputAttributes(['dir' => 'ltr']),
                            TextInput::make('description_ar')->label('وصف (عربي)'),
                            TextInput::make('description_en')->label('وصف (EN)')->extraInputAttributes(['dir' => 'ltr']),
                        ]),
                        TextInput::make('price')->label('السعر')->numeric(),
                    ])->reorderable()->collapsible()->defaultItems(0)->addActionLabel('إضافة صنف')
                        ->itemLabel(fn (array $state) => $state['name_ar'] ?? null),
                ])
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state) => $state['name_ar'] ?? null)
                ->defaultItems(0)
                ->addActionLabel('إضافة قسم');
        }

        if ($extra) {
            $tabs[] = Tab::make($def['items'][1] ?? 'المحتوى والوسائط')->schema($extra);
        }

        return [Tabs::make('content')->tabs($tabs)->columnSpanFull()];
    }

    /** Site the form being built belongs to (set by components(), read by image fields). */
    private static int|string|null $siteId = null;

    private static function withPicker(FileUpload $field, bool $multiple = false): FileUpload
    {
        return self::$siteId ? ImagePicker::attach($field, self::$siteId, $multiple) : $field;
    }

    private static function field(string $name, array $spec, string $dir, ?string $locale = null)
    {
        [$kind, $label] = $spec;
        $hint = $spec[2] ?? null;

        $component = match ($kind) {
            self::AREA => Textarea::make($name)->rows(3)->autosize(),
            self::IMAGE => self::withPicker(FileUpload::make($name)->image()->disk('public')->directory($dir)->visibility('public')->maxSize(4096)->imagePreviewHeight('120')),
            self::IMAGES => self::withPicker(FileUpload::make($name)->image()->multiple()->reorderable()->panelLayout('grid')->disk('public')->directory($dir)->visibility('public')->maxSize(4096)->maxFiles(24), multiple: true),
            self::NUMBER => TextInput::make($name)->numeric(),
            self::URL => TextInput::make($name)->url()->maxLength(2048),
            default => TextInput::make($name)->maxLength(500),
        };

        $component->label($label);

        if ($hint) {
            $component->helperText($hint);
        }

        if ($locale === 'en' && in_array($kind, [self::TEXT, self::AREA], true)) {
            $component->extraInputAttributes(['dir' => 'ltr']);
        }

        return $component;
    }
}
