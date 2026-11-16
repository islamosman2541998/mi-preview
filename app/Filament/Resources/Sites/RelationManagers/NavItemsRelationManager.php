<?php

namespace App\Filament\Resources\Sites\RelationManagers;

use App\Enums\NavItemType;
use App\Models\NavItem;
use App\Models\Site;
use App\Services\SiteBuilder;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Navbar items: drag to reorder; one level of dropdown via "parent". */
class NavItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'navItems';

    protected static ?string $title = 'القائمة';

    protected static ?string $modelLabel = 'عنصر قائمة';

    protected static ?string $pluralModelLabel = 'عناصر القائمة';

    public function form(Schema $schema): Schema
    {
        /** @var Site $site */
        $site = $this->getOwnerRecord();

        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('label_ar')->label('النص (عربي)')->required()->maxLength(60),
                TextInput::make('label_en')->label('النص (EN)')->maxLength(60)->extraInputAttributes(['dir' => 'ltr']),
            ]),
            Grid::make(2)->schema([
                Select::make('type')
                    ->label('النوع')
                    ->options(NavItemType::class)
                    ->default(NavItemType::Page)
                    ->required()
                    ->live()
                    ->selectablePlaceholder(false),

                // the target input changes with the type
                Select::make('target')
                    ->label('الصفحة')
                    ->options(fn () => $site->pages()->pluck('title_ar', 'slug'))
                    ->required()
                    ->visible(fn (Get $get) => self::type($get) === NavItemType::Page),
                Select::make('target')
                    ->label('القسم في الصفحة الرئيسية')
                    ->options(fn () => $site->homePage?->sections()->get()
                        ->mapWithKeys(fn ($s) => [$s->anchor() => $s->type->getLabel()]) ?? [])
                    ->required()
                    ->visible(fn (Get $get) => self::type($get) === NavItemType::Anchor),
                TextInput::make('target')
                    ->label('الرابط')
                    ->url()
                    ->required()
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->visible(fn (Get $get) => self::type($get) === NavItemType::External),
                TextInput::make('target')
                    ->label('الرقم')
                    ->tel()
                    ->helperText('اتركه فارغًا لاستخدام رقم الشركة.')
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->visible(fn (Get $get) => in_array(self::type($get), [NavItemType::Whatsapp, NavItemType::Phone], true)),
            ]),
            Grid::make(2)->schema([
                Select::make('parent_id')
                    ->label('داخل قائمة منسدلة تحت')
                    ->placeholder('— عنصر رئيسي —')
                    ->options(fn (?NavItem $record) => $site->navItems()
                        ->whereNull('parent_id')
                        ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                        ->pluck('label_ar', 'id'))
                    // an item that already has children can't become a child (one level only)
                    ->disabled(fn (?NavItem $record) => $record?->children()->exists()),
                Toggle::make('is_cta')->label('زر بارز (CTA)')->inline(false),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->paginated(false)
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent')) // parameter must be named $query
            ->columns([
                TextColumn::make('label_ar')
                    ->label('النص')
                    ->formatStateUsing(fn (NavItem $record) => ($record->parent_id ? '↳ ' : '').$record->label_ar)
                    ->description(fn (NavItem $record) => $record->label_en),
                TextColumn::make('type')->label('النوع')->badge()->color('gray'),
                TextColumn::make('target')
                    ->label('الوجهة')
                    ->state(fn (NavItem $record) => $record->href($this->getOwnerRecord(), null, $this->getOwnerRecord()->homePage?->slug))
                    ->color('gray')
                    ->limit(40),
                TextColumn::make('parent.label_ar')->label('تحت')->placeholder('—'),
                IconColumn::make('is_cta')->label('CTA')->boolean()->alignCenter(),
            ])
            ->headerActions([
                Action::make('regenerate')
                    ->label('إعادة التوليد من الصفحات')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('سيتم حذف كل عناصر القائمة الحالية وإنشاؤها من جديد من الصفحات المنشورة.')
                    ->action(function () {
                        app(SiteBuilder::class)->generateNav($this->getOwnerRecord());
                        Notification::make()->title('تم توليد القائمة')->success()->send();
                    }),
                CreateAction::make()
                    ->label('إضافة عنصر')
                    ->mutateDataUsing(fn (array $data) => $data + ['sort' => (int) $this->getOwnerRecord()->navItems()->max('sort') + 1]),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ]);
    }

    private static function type(Get $get): ?NavItemType
    {
        $type = $get('type');

        return $type instanceof NavItemType ? $type : NavItemType::tryFrom((string) $type);
    }
}
