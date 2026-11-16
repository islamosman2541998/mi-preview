<?php

namespace App\Filament\Resources\Sites\Tables;

use App\Enums\SiteStatus;
use App\Filament\Resources\Sites\SiteActions;
use App\Models\Site;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('company_name_ar')
                    ->label('الشركة')
                    ->description(fn (Site $record) => $record->host())
                    ->searchable(['company_name_ar', 'company_name_en', 'slug'])
                    ->sortable(),
                TextColumn::make('industry.name_ar')
                    ->label('النشاط')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->sortable(),
                TextColumn::make('views_count')
                    ->label('الزيارات')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('last_viewed_at')
                    ->label('آخر مشاهدة')
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder('لم يُفتح بعد')
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('أُرسل')
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('أُنشئ')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(SiteStatus::class)->multiple(),
                SelectFilter::make('industry')->label('النشاط')->relationship('industry', 'name_ar'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    SiteActions::copyLink(),
                    SiteActions::copyPitch(),
                    SiteActions::preview(),
                    SiteActions::openAsOwner(),
                    SiteActions::visits(),
                    SiteActions::markWon(),
                    SiteActions::markLost(),
                    SiteActions::duplicate(),
                    SiteActions::export(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('archive')
                        ->label('أرشفة')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => SiteStatus::Archived]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
