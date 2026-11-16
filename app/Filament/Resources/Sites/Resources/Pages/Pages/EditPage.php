<?php

namespace App\Filament\Resources\Sites\Resources\Pages\Pages;

use App\Enums\Locale;
use App\Filament\Resources\Sites\Resources\Pages\PageResource;
use App\Filament\Widgets\AiRunProgress;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return 'صفحة: '.$this->record->title_ar;
    }

    protected function getHeaderActions(): array
    {
        $site = $this->record->site;

        return [
            Action::make('previewAr')
                ->label('معاينة')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $site->url(Locale::Ar, $this->record->is_home ? null : $this->record->slug))
                ->openUrlInNewTab(),
            Action::make('previewEn')
                ->label('EN')
                ->color('gray')
                ->url(fn () => $site->url(Locale::En, $this->record->is_home ? null : $this->record->slug))
                ->openUrlInNewTab(),
            DeleteAction::make()->hidden(fn () => $this->record->is_home),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [AiRunProgress::class];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ الصفحة';
    }
}
