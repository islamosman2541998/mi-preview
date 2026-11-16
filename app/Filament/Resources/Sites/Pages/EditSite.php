<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Filament\Resources\Sites\Concerns\HandlesLogoTools;
use App\Filament\Resources\Sites\Concerns\MarksSitesSent;
use App\Filament\Resources\Sites\SiteActions;
use App\Filament\Resources\Sites\SiteResource;
use App\Filament\Support\AiActions;
use App\Filament\Widgets\AiRunProgress;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSite extends EditRecord
{
    use HandlesLogoTools;
    use MarksSitesSent;

    protected static string $resource = SiteResource::class;

    public function getTitle(): string
    {
        return $this->record->company_name_ar;
    }

    protected function getHeaderActions(): array
    {
        return [
            AiActions::generateSite(),
            ...SiteActions::all(),
            ActionGroup::make([
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])->icon('heroicon-o-trash')->color('danger')->label('حذف'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [AiRunProgress::class];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['socials'] = array_filter($data['socials'] ?? []) ?: null;
        $data['seo'] = array_filter($data['seo'] ?? [], 'filled') ?: null;

        return $data;
    }
}
