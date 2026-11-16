<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Enums\SiteStatus;
use App\Filament\Resources\Sites\Concerns\MarksSitesSent;
use App\Filament\Resources\Sites\SiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSites extends ListRecords
{
    use MarksSitesSent;

    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('موقع جديد'),
        ];
    }

    public function getTabs(): array
    {
        $tab = fn (string $label, array $statuses) => Tab::make($label)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $statuses)); // Filament injects by parameter name

        return [
            'active' => $tab('الحالية', ['draft', 'sent', 'viewed']),
            'viewed' => $tab('تمت مشاهدتها', [SiteStatus::Viewed->value]),
            'won' => $tab('تم التعاقد', [SiteStatus::Won->value]),
            'closed' => $tab('مغلقة', [SiteStatus::Lost->value, SiteStatus::Archived->value]),
            'all' => Tab::make('الكل'),
        ];
    }
}
