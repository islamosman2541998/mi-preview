<?php

namespace App\Filament\Resources\Sites\Schemas;

use App\Ai\AiProviders;
use App\Ai\AiRuns;
use App\Enums\Locale;
use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Models\Industry;
use App\Models\Site;
use App\Rules\SiteSlug;
use App\Services\RemoveBg;
use App\Services\SiteBuilder;
use App\Services\SocialProfileImporter;
use App\Services\Stock\StockPhotos;
use App\Support\Palette;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Field groups shared by the create wizard (one step per group) and the
 * edit page (one tab per group).
 */
class SiteForm
{
    public static function socialImport(): array
    {
        return [
            TextInput::make('source_url')
                ->label('رابط صفحة Facebook أو حساب Instagram')
                ->placeholder('https://www.instagram.com/company')
                ->helperText(fn () => app(SocialProfileImporter::class)->configured()
                    ? 'الصق رابط الصفحة مباشرة ثم اضغط استيراد. يمكنك تخطي هذه الخطوة وإدخال البيانات يدويًا.'
                    : 'أضف META_ACCESS_TOKEN في ملف .env لتفعيل الاستيراد، أو تخطَّ الخطوة وأدخل البيانات يدويًا.')
                ->url()
                ->maxLength(2048)
                ->extraInputAttributes(['dir' => 'ltr']),
            Hidden::make('source_data'),
            ViewField::make('social_import_tools')
                ->hiddenLabel()
                ->view('filament.forms.social-import')
                ->dehydrated(false),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('site')->tabs([
                Tab::make('البيانات الأساسية')->schema(self::basic()),
                Tab::make('التواصل')->schema(self::contact()),
                Tab::make('الشعار والألوان')->schema(self::branding()),
                Tab::make('القالب والإعدادات')->schema([...self::template(), ...self::settings(withStatus: true), ...self::seo()]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    public static function basic(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('company_name_ar')
                    ->label('اسم الشركة (عربي)')
                    ->required()
                    ->maxLength(120),
                TextInput::make('company_name_en')
                    ->label('اسم الشركة (EN)')
                    ->maxLength(120)
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation) {
                        if ($operation === 'create' && blank($get('slug')) && filled($state)) {
                            $set('slug', Str::limit(Str::slug($state), 40, ''));
                        }
                    }),
            ]),
            TextInput::make('slug')
                ->label('رابط الموقع')
                ->helperText('حروف إنجليزي صغيرة وأرقام وشرطة، من 3 إلى 40 حرف.')
                ->prefix('https://')
                ->suffix('.'.config('preview.base_domain'))
                ->required()
                ->rules([new SiteSlug])
                ->unique(Site::class, 'slug', ignoreRecord: true)
                ->extraAttributes(['dir' => 'ltr']),
            Select::make('industry_id')
                ->label('النشاط')
                ->relationship('industry', 'name_ar')
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, Set $set, string $operation) {
                    // a new site starts from the industry palette (still editable)
                    if ($operation === 'create' && ($palette = Industry::find($state)?->default_palette)) {
                        foreach ($palette as $key => $color) {
                            $set("palette.{$key}", $color);
                        }
                    }
                }),
            Textarea::make('short_description')
                ->label('وصف مختصر للنشاط')
                ->helperText('يُستخدم لاحقًا لتوليد المحتوى بالذكاء الاصطناعي.')
                ->rows(3),
        ];
    }

    public static function contact(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('phone')->label('الهاتف')->tel()->maxLength(30)->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('whatsapp')->label('واتساب')->tel()->maxLength(30)
                    ->helperText('بالصيغة الدولية، مثال: +20 100 123 4567')
                    ->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('email')->label('البريد الإلكتروني')->email()->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
            ]),
            Grid::make(2)->schema([
                TextInput::make('address_ar')->label('العنوان (عربي)')->maxLength(255),
                TextInput::make('address_en')->label('العنوان (EN)')->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
            ]),
            TextInput::make('map_url')->label('رابط الموقع على الخريطة')->url()->maxLength(2048)->extraInputAttributes(['dir' => 'ltr']),
            Section::make('حسابات التواصل الاجتماعي')->collapsible()->collapsed()->schema([
                Grid::make(2)->schema(collect(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'x' => 'X'])
                    ->map(fn ($label, $key) => TextInput::make("socials.{$key}")->label($label)->url()->extraInputAttributes(['dir' => 'ltr']))
                    ->values()->all()),
            ]),
        ];
    }

    public static function branding(): array
    {
        $colors = [
            'primary' => 'الأساسي',
            'secondary' => 'الثانوي (الأزرار)',
            'accent' => 'لون مميز',
            'text' => 'النصوص',
            'bg' => 'الخلفية',
        ];

        return [
            Grid::make(2)->schema([
                FileUpload::make('logo_path')
                    ->label('الشعار')
                    ->helperText('ارفع الشعار بأي صيغة صورة. عند إنشاء موقع جديد تُستخرج الألوان منه تلقائيًا.')
                    ->image()
                    ->disk('public')->directory('sites/logos')->visibility('public')
                    ->maxSize(2048)
                    ->imagePreviewHeight('100')
                    ->live()
                    ->afterStateUpdated(function ($livewire, string $operation, $state) {
                        // a fresh logo on a new site: colours follow the logo (still editable)
                        if ($operation === 'create' && filled($state) && method_exists($livewire, 'extractLogoPalette')) {
                            $livewire->extractLogoPalette(quiet: true);
                        }
                    }),
                FileUpload::make('logo_nobg_path')
                    ->label('الشعار بدون خلفية')
                    ->helperText('يظهر في شاشة التحميل. استخدم زر "إزالة الخلفية تلقائيًا" أو ارفعه بأي صيغة صورة.')
                    ->image()
                    ->disk('public')->directory('sites/logos')->visibility('public')
                    ->maxSize(2048)
                    ->imagePreviewHeight('100'),
            ]),
            ViewField::make('logo_tools')
                ->hiddenLabel()
                ->view('filament.forms.logo-tools')
                ->viewData(['remoteEnabled' => app(RemoveBg::class)->configured()])
                ->dehydrated(false),
            Section::make('الألوان')
                ->description('تُملأ من الشعار أو من النشاط المختار، ويمكن تعديلها يدويًا.')
                ->schema([
                    Grid::make(5)->schema(collect($colors)->map(
                        fn ($label, $key) => ColorPicker::make("palette.{$key}")->label($label)->hex()->live(onBlur: true)
                            ->default(Palette::DEFAULTS[$key])
                    )->values()->all()),
                    Text::make(fn (Get $get) => self::contrastReport($get('palette') ?? [])),
                ]),
        ];
    }

    public static function template(): array
    {
        return [
            Grid::make(2)->schema([
                Radio::make('template')
                    ->label('القالب')
                    ->options(collect(config('preview.templates'))->map(fn ($t) => $t['ar'].' — '.$t['en']))
                    ->descriptions(collect(config('preview.templates'))->map(fn ($t) => 'مناسب لـ: '.($t['for'] ?? '')))
                    ->default(config('preview.default_template'))
                    ->required(),
                Select::make('font_key')
                    ->label('الخطوط')
                    ->options(collect(config('preview.fonts'))->map(fn ($f) => $f['label']))
                    ->placeholder('افتراضي القالب'),
            ]),
        ];
    }

    /** Edit page: SEO meta (written by the AI run, editable). */
    public static function seo(): array
    {
        return [
            Section::make('SEO (محركات البحث)')
                ->description('تُكتب تلقائيًا مع توليد المحتوى، وتُستخدم في الموقع النهائي — صفحات المعاينة نفسها لا تُفهرس.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('seo.title_ar')->label('عنوان الصفحة (عربي)')->maxLength(70),
                        TextInput::make('seo.title_en')->label('Title (EN)')->maxLength(70)->extraInputAttributes(['dir' => 'ltr']),
                        Textarea::make('seo.description_ar')->label('الوصف (عربي)')->rows(2)->maxLength(200),
                        Textarea::make('seo.description_en')->label('Description (EN)')->rows(2)->maxLength(200)->extraInputAttributes(['dir' => 'ltr']),
                    ]),
                ]),
        ];
    }

    public static function settings(bool $withStatus = false): array
    {
        return array_values(array_filter([
            $withStatus ? ToggleButtons::make('status')->label('الحالة')->options(SiteStatus::class)->inline()->required() : null,
            Grid::make(3)->schema([
                Select::make('default_locale')->label('اللغة الافتراضية')->options(Locale::class)->default('ar')->required()->selectablePlaceholder(false),
                Toggle::make('preloader_enabled')->label('شاشة التحميل بالشعار')->default(true)->inline(false),
                Toggle::make('notify_enabled')->label('تنبيهات تيليجرام')->default(true)->inline(false),
            ]),
        ]));
    }

    /** Wizard step: which pages/sections the industry blueprint will create. */
    public static function pagesPreview(): array
    {
        return [
            Toggle::make('build_pages')
                ->label('إنشاء الصفحات والأقسام تلقائيًا حسب النشاط')
                ->default(true)
                ->live(),
            Toggle::make('ai_generate')
                ->label('كتابة المحتوى بالذكاء الاصطناعي بعد الإنشاء')
                ->helperText(fn () => app(AiRuns::class)->configured()
                    ? 'يكتب '.app(AiProviders::class)->default()?->label().' نصوص كل الأقسام بالعربية والإنجليزية من الوصف المختصر'.(app(StockPhotos::class)->configured() ? '، ويختار لها صورًا.' : '.').' يستغرق دقيقة إلى ثلاث.'
                    : 'يتطلب AI_API_KEY أو GEMINI_API_KEY في ملف .env — بدونه يُنشأ الموقع بمحتوى افتراضي.')
                ->default(fn () => app(AiRuns::class)->configured())
                ->disabled(fn () => ! app(AiRuns::class)->configured())
                ->visible(fn (Get $get) => (bool) $get('build_pages')),
            Text::make(function (Get $get) {
                if (! $get('build_pages')) {
                    return 'سيتم إنشاء الموقع بدون صفحات — أضفها يدويًا بعد الحفظ.';
                }

                $blueprint = Industry::find($get('industry_id'))?->sections_blueprint ?: SiteBuilder::DEFAULT_BLUEPRINT;
                $rows = collect($blueprint['pages'] ?? [])->map(function ($p) {
                    $sections = collect($p['sections'] ?? [])
                        ->map(fn ($s) => SectionType::tryFrom(is_array($s) ? $s['type'] : $s)?->getLabel())
                        ->filter()->implode('، ');

                    return '<li><strong>'.e($p['title']['ar'] ?? $p['slug']).'</strong>: '.e($sections).'</li>';
                })->implode('');

                return new HtmlString('<ul style="display:grid;gap:6px;padding-inline-start:18px;list-style:disc">'.$rows.'</ul>');
            }),
        ];
    }

    private static function contrastReport(array $palette): HtmlString
    {
        $p = array_merge(Palette::DEFAULTS, array_filter($palette, fn ($v) => Palette::normalize($v)));

        $checks = [
            'النص على الخلفية' => [$p['text'], $p['bg']],
            'نص الأزرار على اللون الثانوي' => [Palette::readableOn($p['secondary'], $p['text']), $p['secondary']],
            'نص أبيض على الأساسي الغامق' => ['#FFFFFF', Palette::mix($p['primary'], '#000000', 0.6)],
        ];

        $items = collect($checks)->map(function ($pair, $label) {
            $ratio = Palette::contrast($pair[0], $pair[1]);
            $ok = $ratio >= 4.5;

            return '<span style="display:inline-flex;align-items:center;gap:6px;margin-inline-end:18px">'
                .'<span style="width:14px;height:14px;border-radius:3px;background:'.$pair[1].';border:1px solid #0002"></span>'
                .e($label).': <strong style="color:'.($ok ? '#15803d' : '#b91c1c').'">'.number_format($ratio, 1).($ok ? ' ✓' : ' — ضعيف').'</strong></span>';
        })->implode('');

        return new HtmlString('<div style="font-size:.85rem">تباين الألوان (WCAG AA ≥ 4.5): '.$items.'</div>');
    }
}
