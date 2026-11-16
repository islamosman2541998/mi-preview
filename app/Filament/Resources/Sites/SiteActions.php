<?php

namespace App\Filament\Resources\Sites;

use App\Enums\SiteStatus;
use App\Filament\Support\Pitch;
use App\Models\Site;
use App\Rules\SiteSlug;
use App\Services\SiteDuplicator;
use App\Services\SiteExporter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;

/**
 * Site actions shared by the list table and the edit page.
 * Copy actions run fully in the browser (clipboard needs the click gesture);
 * copying the pitch also marks a draft as "sent" via $wire.markSiteSent().
 */
class SiteActions
{
    public static function all(): array
    {
        return [
            self::copyLink(),
            self::copyPitch(),
            self::preview(),
            self::openAsOwner(),
            self::visits(),
            ActionGroup::make([
                self::markWon(),
                self::markLost(),
                self::changeStatus(),
                self::duplicate(),
                self::export(),
            ])->dropdown()->label('المزيد')->icon(Heroicon::OutlinedEllipsisVertical),
        ];
    }

    private static function copyJs(string $text, string $message, ?string $after = null): string
    {
        return 'window.navigator.clipboard.writeText('.Js::from($text).')'
            .'.then(() => { new FilamentNotification().title('.Js::from($message).').success().send(); '.($after ?? '').' })'
            .'.catch(() => new FilamentNotification().title('.Js::from('تعذر النسخ — انسخ يدويًا').').danger().send())';
    }

    public static function copyLink(): Action
    {
        return Action::make('copyLink')
            ->label('نسخ الرابط')
            ->icon(Heroicon::OutlinedLink)
            ->color('gray')
            ->alpineClickHandler(fn (Site $record) => self::copyJs($record->url(), 'تم نسخ الرابط'));
    }

    public static function copyPitch(): Action
    {
        return Action::make('copyPitch')
            ->label('نسخ رسالة واتساب')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->alpineClickHandler(fn (Site $record) => self::copyJs(
                Pitch::message($record),
                'تم نسخ الرسالة',
                '$wire.markSiteSent('.$record->id.');',
            ));
    }

    public static function preview(): Action
    {
        return Action::make('preview')
            ->label('فتح الموقع')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (Site $record) => $record->url())
            ->openUrlInNewTab();
    }

    public static function openAsOwner(): Action
    {
        return Action::make('openAsOwner')
            ->label('فتح كمالك')
            ->tooltip('يفتح الموقع بدون تسجيل زيارة أو إرسال تنبيه — ويستثني هذا المتصفح دائمًا')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('gray')
            ->url(fn (Site $record) => route('admin.open-as-owner', $record))
            ->openUrlInNewTab();
    }

    public static function visits(): Action
    {
        return Action::make('visits')
            ->label(fn (Site $record) => 'الزيارات ('.$record->visits()->count().')')
            ->icon(Heroicon::OutlinedChartBar)
            ->color('gray')
            ->url(fn (Site $record) => SiteResource::getUrl('visits', ['record' => $record]));
    }

    public static function markWon(): Action
    {
        return Action::make('markWon')
            ->label('تم التعاقد')
            ->icon(Heroicon::OutlinedTrophy)
            ->color('success')
            ->requiresConfirmation()
            ->hidden(fn (Site $record) => $record->status === SiteStatus::Won)
            ->action(function (Site $record) {
                $record->update(['status' => SiteStatus::Won]);
                Notification::make()->title('مبروك! تم تسجيل التعاقد')->success()->send();
            });
    }

    public static function markLost(): Action
    {
        return Action::make('markLost')
            ->label('لم يتم')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->hidden(fn (Site $record) => $record->status === SiteStatus::Lost)
            ->action(fn (Site $record) => $record->update(['status' => SiteStatus::Lost]));
    }

    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('تغيير الحالة')
            ->icon(Heroicon::OutlinedArrowPath)
            ->fillForm(fn (Site $record) => ['status' => $record->status])
            ->schema([
                ToggleButtons::make('status')->label('الحالة')->options(SiteStatus::class)->inline()->required(),
            ])
            ->action(function (Site $record, array $data) {
                $status = SiteStatus::from($data['status'] instanceof SiteStatus ? $data['status']->value : $data['status']);
                $record->update([
                    'status' => $status,
                    'sent_at' => $status === SiteStatus::Sent ? ($record->sent_at ?? now()) : $record->sent_at,
                ]);
            });
    }

    public static function duplicate(): Action
    {
        return Action::make('duplicate')
            ->label('نسخ الموقع')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->modalDescription('ينسخ الصفحات والأقسام والقائمة إلى موقع جديد كمسودة.')
            ->fillForm(fn (Site $record) => [
                'company_name_ar' => $record->company_name_ar,
                'company_name_en' => $record->company_name_en,
            ])
            ->schema([
                TextInput::make('slug')
                    ->label('رابط الموقع الجديد')
                    ->prefix('https://')
                    ->suffix('.'.config('preview.base_domain'))
                    ->required()
                    ->rules([new SiteSlug])
                    ->unique(Site::class, 'slug')
                    ->extraAttributes(['dir' => 'ltr']),
                TextInput::make('company_name_ar')->label('اسم الشركة (عربي)')->required(),
                TextInput::make('company_name_en')->label('اسم الشركة (EN)'),
            ])
            ->action(function (Site $record, array $data, $livewire) {
                $copy = app(SiteDuplicator::class)->duplicate($record, $data['slug'], $data['company_name_ar'], $data['company_name_en'] ?? null);
                Notification::make()->title('تم نسخ الموقع')->success()->send();
                $livewire->redirect(SiteResource::getUrl('edit', ['record' => $copy]));
            });
    }

    /** Zip with site.json + media/ to seed the client's real site. */
    public static function export(): Action
    {
        return Action::make('export')
            ->label('تصدير (JSON + الصور)')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->action(function (Site $record) {
                try {
                    $exporter = app(SiteExporter::class);
                    $path = $exporter->export($record);
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()->title('تعذّر تجهيز ملف التصدير')->danger()->send();

                    return null;
                }

                return response()->download($path, $exporter->filename($record))->deleteFileAfterSend();
            });
    }
}
