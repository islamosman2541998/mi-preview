<?php

namespace App\Filament\Resources\Sites\Resources\Pages\RelationManagers;

use App\Enums\SectionType;
use App\Filament\Support\AiActions;
use App\Filament\Support\SectionFields;
use App\Models\Page;
use App\Models\Section;
use App\Services\SectionDefaults;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Sections of one page: drag to reorder, per-type form with ar/en tabs.
 * Content is edited through the "c" state key (see SectionFields).
 */
class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'أقسام الصفحة';

    protected static ?string $modelLabel = 'قسم';

    protected static ?string $pluralModelLabel = 'الأقسام';

    /** Variants a template understands, per section type. */
    public const VARIANTS = [
        'services' => ['list' => 'قائمة (افتراضي)', 'grid' => 'مربعات'],
        'projects' => ['feature' => 'مشروع رئيسي + قائمة', 'grid' => 'شبكة'],
    ];

    public function form(Schema $schema): Schema
    {
        $siteId = $this->getOwnerRecord()->site_id;

        return $schema->components([
            Grid::make(2)->schema([
                Select::make('type')
                    ->label('نوع القسم')
                    ->options(SectionType::class)
                    ->required()
                    ->live()
                    ->disabledOn('edit')
                    ->afterStateUpdated(function ($state, Set $set) {
                        $type = $state instanceof SectionType ? $state : SectionType::tryFrom((string) $state);
                        $set('variant', null);
                        $set('c', $type ? SectionFields::toForm($type, SectionDefaults::for($type, $this->getOwnerRecord()->site)) : []);
                    }),
                Select::make('variant')
                    ->label('الشكل')
                    ->placeholder('افتراضي')
                    ->options(fn (Get $get) => self::VARIANTS[self::typeOf($get('type'))?->value] ?? [])
                    ->visible(fn (Get $get) => isset(self::VARIANTS[self::typeOf($get('type'))?->value])),
            ]),
            Group::make()
                ->schema(fn (Get $get) => SectionFields::components(self::typeOf($get('type')), $siteId))
                ->key(fn (Get $get) => 'section-fields-'.(self::typeOf($get('type'))?->value ?? 'none'))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->paginated(false)
            ->columns([
                TextColumn::make('sort')->label('#')->width('1%')->state(fn ($rowLoop) => $rowLoop->iteration),
                TextColumn::make('type')->label('النوع')->badge()->color('gray'),
                TextColumn::make('heading')
                    ->label('العنوان')
                    ->state(fn (Section $record) => $record->contentFor('ar')['title'] ?? null)
                    ->placeholder('—')
                    ->limit(60),
                TextColumn::make('variant')->label('الشكل')->placeholder('افتراضي')->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_visible')->label('ظاهر'),
            ])
            ->headerActions([
                AiActions::generatePage(fn () => $this->getOwnerRecord()),
                CreateAction::make()
                    ->label('إضافة قسم')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->mutateDataUsing(fn (array $data) => $this->toModel($data) + [
                        'sort' => (int) $this->getOwnerRecord()->sections()->max('sort') + 1,
                    ]),
            ])
            ->recordActions([
                AiActions::regenerateSection(),
                EditAction::make()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->mutateRecordDataUsing(fn (array $data, Section $record) => $data + [
                        'c' => SectionFields::toForm($record->type, $record->content),
                    ])
                    ->mutateDataUsing(fn (array $data, Section $record) => $this->toModel($data + ['type' => $record->type])),
                ReplicateAction::make()
                    ->label('تكرار')
                    ->excludeAttributes(['sort'])
                    ->beforeReplicaSaved(fn (Section $replica, Section $record) => $replica->sort = $record->sort + 1),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([AiActions::regenerateSelected(), DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading('لا توجد أقسام بعد')
            ->emptyStateDescription('أضف أول قسم لهذه الصفحة.');
    }

    private function toModel(array $data): array
    {
        $type = self::typeOf($data['type'] ?? null);
        $data['content'] = SectionFields::fromForm($type, $data['c'] ?? []);
        unset($data['c']);

        return $data;
    }

    private static function typeOf(mixed $value): ?SectionType
    {
        return $value instanceof SectionType ? $value : SectionType::tryFrom((string) $value);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Page;
    }
}
