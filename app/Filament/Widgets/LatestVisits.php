<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Sites\SiteResource;
use App\Models\Visit;
use App\Tracking\VisitMessages;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestVisits extends TableWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected static ?string $heading = 'آخر الزيارات';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Visit::query()->with('site')->withCount([
                'events as clicks_count' => fn (Builder $q) => $q->whereIn('type', ['whatsapp_click', 'phone_click', 'cta_click']),
            ]))
            ->defaultSort('started_at', 'desc')
            ->paginated([10])
            ->recordUrl(fn (Visit $record) => $record->site
                ? SiteResource::getUrl('visits', ['record' => $record->site_id, 'visit' => $record->id])
                : null)
            ->columns([
                TextColumn::make('site.company_name_ar')->label('الشركة'),
                TextColumn::make('started_at')->label('الوقت')->since()->dateTimeTooltip(),
                TextColumn::make('duration_seconds')->label('المدة')->formatStateUsing(fn (int $state) => VisitMessages::duration($state)),
                TextColumn::make('device')->label('الجهاز')->badge()->color('gray'),
                TextColumn::make('country')->label('الدولة')->placeholder('—'),
                IconColumn::make('is_return')->label('متكررة')->boolean()->trueIcon('heroicon-s-arrow-path')->falseIcon('')->trueColor('warning'),
                TextColumn::make('clicks_count')->label('ضغطات تواصل')->badge()->color(fn (int $state) => $state > 0 ? 'danger' : 'gray'),
                IconColumn::make('ended_at')->label('الآن')->state(fn (Visit $record) => $record->isOpen())->boolean()->trueIcon('heroicon-s-signal')->falseIcon('')->trueColor('success'),
            ])
            ->emptyStateHeading('لا توجد زيارات بعد');
    }
}
