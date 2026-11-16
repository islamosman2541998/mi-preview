<?php

namespace App\Filament\Resources\Sites\RelationManagers;

use App\Filament\Resources\Sites\Resources\Pages\PageResource;
use App\Filament\Resources\Sites\Resources\Pages\Schemas\PageForm;
use App\Models\Page;
use App\Services\SiteBuilder;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    protected static ?string $title = 'الصفحات';

    protected static ?string $modelLabel = 'صفحة';

    protected static ?string $pluralModelLabel = 'الصفحات';

    public function form(Schema $schema): Schema
    {
        return $schema->components(PageForm::fields(fn () => $this->getOwnerRecord()->getKey()));
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->paginated(false)
            ->recordUrl(fn (Page $record) => PageResource::getUrl('edit', ['site' => $this->getOwnerRecord(), 'record' => $record]))
            ->columns([
                TextColumn::make('title_ar')->label('الصفحة')->description(fn (Page $record) => $record->title_en),
                TextColumn::make('slug')->label('الرابط')->formatStateUsing(fn (Page $record) => $record->path())->color('gray')->extraAttributes(['dir' => 'ltr']),
                IconColumn::make('is_home')->label('الرئيسية')->boolean()->trueIcon('heroicon-s-home')->falseIcon('')->alignCenter(),
                TextColumn::make('sections_count')->label('الأقسام')->counts('sections')->alignCenter(),
                ToggleColumn::make('is_published')->label('منشورة')->disabled(fn (Page $record) => $record->is_home),
            ])
            ->headerActions([
                Action::make('regenerateNav')
                    ->label('تحديث القائمة من الصفحات')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('سيتم استبدال عناصر القائمة الحالية بروابط الصفحات المنشورة وزر التواصل.')
                    ->action(function () {
                        app(SiteBuilder::class)->generateNav($this->getOwnerRecord());
                        Notification::make()->title('تم تحديث القائمة')->success()->send();
                    }),
                CreateAction::make()
                    ->label('إضافة صفحة')
                    ->mutateDataUsing(fn (array $data) => $data + [
                        'sort' => (int) $this->getOwnerRecord()->pages()->max('sort') + 1,
                    ]),
            ])
            ->recordActions([
                Action::make('sections')
                    ->label('الأقسام')
                    ->icon('heroicon-o-squares-2x2')
                    ->url(fn (Page $record) => PageResource::getUrl('edit', ['site' => $this->getOwnerRecord(), 'record' => $record])),
                Action::make('makeHome')
                    ->label('اجعلها الرئيسية')
                    ->icon('heroicon-o-home')
                    ->color('gray')
                    ->hidden(fn (Page $record) => $record->is_home)
                    ->requiresConfirmation()
                    ->action(function (Page $record) {
                        DB::transaction(function () use ($record) {
                            $record->site->pages()->update(['is_home' => false]);
                            $record->update(['is_home' => true, 'is_published' => true]);
                        });
                    }),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton()->hidden(fn (Page $record) => $record->is_home),
            ]);
    }
}
