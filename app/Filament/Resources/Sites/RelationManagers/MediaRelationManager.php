<?php

namespace App\Filament\Resources\Sites\RelationManagers;

use App\Enums\MediaSource;
use App\Filament\Support\ImagePicker;
use App\Models\Media;
use App\Services\MediaLibrary;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

/** Site media library: uploads, Pexels imports, and images placed in sections. */
class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'الوسائط';

    protected static ?string $modelLabel = 'صورة';

    protected static ?string $pluralModelLabel = 'الصور';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            ImagePicker::resizeOnUpload(FileUpload::make('path'))
                ->label('الصورة')
                ->image()
                ->required()
                ->disk('public')
                ->directory(fn () => 'sites/'.$this->getOwnerRecord()->getKey().'/media')
                ->visibility('public')
                ->maxSize(4096)
                ->hiddenOn('edit'),
            Grid::make(2)->schema([
                TextInput::make('alt_ar')->label('وصف الصورة (عربي)')->maxLength(255),
                TextInput::make('alt_en')->label('وصف الصورة (EN)')->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
            ]),
            TextInput::make('credit')->label('حقوق الصورة')->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('path')->label('')->disk('public')->imageHeight(56),
                TextColumn::make('alt_ar')->label('الوصف')->placeholder('—')->limit(40),
                TextColumn::make('source')->label('المصدر')->badge()->color('gray'),
                TextColumn::make('credit')->label('الحقوق')->placeholder('—')->toggleable()
                    ->url(fn (Media $record) => $record->meta['url'] ?? null, shouldOpenInNewTab: true),
            ])
            ->headerActions([
                ImagePicker::libraryAction(fn () => $this->getOwnerRecord()->getKey()),
                CreateAction::make()->label('رفع صورة')->mutateDataUsing(fn (array $data) => $data + ['source' => MediaSource::Upload]),
            ])
            ->recordActions([
                Action::make('open')->label('فتح')->icon('heroicon-o-arrow-top-right-on-square')->iconButton()->color('gray')
                    ->url(fn (Media $record) => $record->url(), shouldOpenInNewTab: true),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton()
                    ->modalDescription(fn (Media $record) => app(MediaLibrary::class)->isUsed($record)
                        ? 'هذه الصورة مستخدمة في أحد الأقسام — سيُحذف السجل فقط ويبقى الملف حتى تزيلها من القسم.'
                        : 'سيتم حذف الصورة نهائيًا.')
                    ->after(fn (Media $record) => self::deleteFileIfUnused($record)),
            ])
            ->toolbarActions([BulkActionGroup::make([
                DeleteBulkAction::make()->after(fn (Collection $records) => $records->each(fn (Media $m) => self::deleteFileIfUnused($m))),
            ])])
            ->emptyStateHeading('لا توجد صور بعد')
            ->emptyStateDescription('ارفع صورًا أو أضفها من Pexels — والصور التي ترفعها داخل الأقسام تظهر هنا تلقائيًا.');
    }

    private static function deleteFileIfUnused(Media $media): void
    {
        $owned = str_starts_with($media->path, 'sites/'.$media->site_id.'/');

        if ($owned && ! app(MediaLibrary::class)->isUsed($media) && ! Media::where('path', $media->path)->exists()) {
            Storage::disk('public')->delete($media->path);
        }
    }
}
