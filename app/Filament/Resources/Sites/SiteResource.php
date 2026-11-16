<?php

namespace App\Filament\Resources\Sites;

use App\Filament\Resources\Sites\Pages\CreateSite;
use App\Filament\Resources\Sites\Pages\EditSite;
use App\Filament\Resources\Sites\Pages\ListSites;
use App\Filament\Resources\Sites\Pages\SiteVisits;
use App\Filament\Resources\Sites\RelationManagers\MediaRelationManager;
use App\Filament\Resources\Sites\RelationManagers\NavItemsRelationManager;
use App\Filament\Resources\Sites\RelationManagers\PagesRelationManager;
use App\Filament\Resources\Sites\Schemas\SiteForm;
use App\Filament\Resources\Sites\Tables\SitesTable;
use App\Models\Site;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $recordTitleAttribute = 'company_name_ar';

    protected static ?string $modelLabel = 'موقع';

    protected static ?string $pluralModelLabel = 'المواقع';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return SiteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SitesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PagesRelationManager::class,
            NavItemsRelationManager::class,
            MediaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSites::route('/'),
            'create' => CreateSite::route('/create'),
            'edit' => EditSite::route('/{record}/edit'),
            // referenced by the Telegram alerts (filament.admin.resources.sites.visits)
            'visits' => SiteVisits::route('/{record}/visits'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['company_name_ar', 'company_name_en', 'slug'];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
