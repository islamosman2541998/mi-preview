<?php

namespace App\Filament\Resources\Sites\Resources\Pages;

use App\Filament\Resources\Sites\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Sites\Resources\Pages\RelationManagers\SectionsRelationManager;
use App\Filament\Resources\Sites\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Sites\SiteResource;
use App\Models\Page;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Nested under a site: /sites/{site}/pages/{record}/edit — page settings + its sections. */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $parentResource = SiteResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'title_ar';

    protected static ?string $modelLabel = 'صفحة';

    protected static ?string $pluralModelLabel = 'الصفحات';

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
