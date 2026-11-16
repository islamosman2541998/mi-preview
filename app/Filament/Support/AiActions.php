<?php

namespace App\Filament\Support;

use App\Ai\AiException;
use App\Ai\AiProviders;
use App\Ai\AiRuns;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Services\Stock\StockPhotos;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/** "Generate with AI" actions: whole site, one page, one section, selected sections. */
class AiActions
{
    public static function generateSite(): Action
    {
        return self::base('aiGenerateSite', 'توليد المحتوى بالذكاء الاصطناعي')
            ->modalHeading('توليد محتوى الموقع بالكامل')
            ->modalDescription('يكتب الذكاء الاصطناعي نصوص كل الأقسام بالعربية والإنجليزية من بيانات الشركة والوصف المختصر، ويستبدل النصوص الحالية. الصور التي اخترتها لا تتغير.')
            ->action(fn (array $data, Site $record, $livewire) => self::start($record, null, $data + ['seo' => true], $livewire));
    }

    /** Header action on a page's sections table. */
    public static function generatePage(Closure $page): Action
    {
        return self::base('aiGeneratePage', 'توليد أقسام الصفحة')
            ->color('gray')
            ->modalHeading('إعادة توليد كل أقسام هذه الصفحة')
            ->action(function (array $data, $livewire) use ($page) {
                /** @var Page $p */
                $p = $page();
                self::start($p->site, $p->sections()->pluck('id')->all(), $data, $livewire);
            });
    }

    public static function regenerateSection(): Action
    {
        return self::base('aiRegenerate', 'إعادة توليد')
            ->iconButton()
            ->color('gray')
            ->tooltip('إعادة توليد بالذكاء الاصطناعي')
            ->modalHeading(fn (Section $record) => 'إعادة توليد قسم «'.$record->type->getLabel().'»')
            ->modalDescription('يعيد كتابة نصوص هذا القسم مع مراعاة باقي الموقع. اكتب تعليمات لو عايز تغيير معيّن.')
            ->action(fn (array $data, Section $record, $livewire) => self::start($record->page->site, [$record->id], $data, $livewire));
    }

    public static function regenerateSelected(): BulkAction
    {
        return BulkAction::make('aiRegenerateSelected')
            ->label('إعادة توليد المحدد بالذكاء الاصطناعي')
            ->icon(Heroicon::OutlinedSparkles)
            ->schema(self::fields())
            ->disabled(fn () => ! app(AiRuns::class)->configured())
            ->action(function (array $data, Collection $records, $livewire) {
                $first = $records->first();
                $first && self::start($first->page->site, $records->pluck('id')->all(), $data, $livewire);
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function base(string $name, string $label): Action
    {
        $configured = app(AiRuns::class)->configured();

        return Action::make($name)
            ->label($label)
            ->icon(Heroicon::OutlinedSparkles)
            ->schema(self::fields())
            ->modalSubmitActionLabel('ابدأ التوليد')
            ->disabled(! $configured)
            ->tooltip($configured ? null : 'أضف AI_API_KEY أو GEMINI_API_KEY في ملف ‎.env لتفعيل التوليد');
    }

    private static function fields(): array
    {
        $stock = array_values(app(StockPhotos::class)->configured());
        $providers = app(AiProviders::class);

        return [
            ToggleButtons::make('provider')
                ->label('النموذج')
                ->options($providers->options())
                ->default($providers->default()?->key())
                ->inline()
                ->required()
                ->visible(count($providers->options()) > 1)
                ->helperText('جرّب الاثنين على نفس الموقع وقارن — التكلفة تظهر بعد كل توليد.'),
            Textarea::make('instructions')
                ->label('تعليمات إضافية (اختياري)')
                ->placeholder('مثال: ركّز على المشاريع الحكومية، أسلوب رسمي، اذكر أننا نعمل في الرياض وجدة…')
                ->rows(3)
                ->maxLength(1500),
            Toggle::make('fill_images')
                ->label('اختيار صور للأقسام الفارغة تلقائيًا'.($stock ? ' من '.$stock[0]->label() : ''))
                ->helperText($stock ? 'يملأ فقط الصور الفارغة — لا يستبدل صورًا اخترتها.' : 'يتطلب PIXABAY_API_KEY في ملف ‎.env.')
                ->default((bool) $stock)
                ->disabled(! $stock),
        ];
    }

    private static function start(Site $site, ?array $sectionIds, array $data, mixed $livewire = null): void
    {
        try {
            app(AiRuns::class)->start($site, $sectionIds, $data);
        } catch (AiException $e) {
            Notification::make()->title($e->getMessage())->warning()->send();

            return;
        }

        // the progress widget on the page listens for this and starts polling
        $livewire?->dispatch('ai-run-started');

        Notification::make()
            ->title('بدأ التوليد')
            ->body('تابع التقدم أعلى الصفحة — يستغرق عادة من دقيقة إلى ثلاث دقائق.')
            ->success()
            ->send();
    }
}
