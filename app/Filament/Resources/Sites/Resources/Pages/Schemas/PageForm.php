<?php

namespace App\Filament\Resources\Sites\Resources\Pages\Schemas;

use App\Models\Page;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/** Page settings — used by the nested page editor and the site's pages relation manager. */
class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::fields());
    }

    /** @param  (Closure(): ?int)|null  $siteId  resolves the owning site when there is no record yet */
    public static function fields(?Closure $siteId = null): array
    {
        $pattern = '/^'.config('preview.page_slug_pattern').'$/';

        return [
            Grid::make(2)->schema([
                TextInput::make('title_ar')->label('العنوان (عربي)')->required()->maxLength(120),
                TextInput::make('title_en')->label('العنوان (EN)')->maxLength(120)->extraInputAttributes(['dir' => 'ltr']),
            ]),
            Grid::make(2)->schema([
                TextInput::make('slug')
                    ->label('رابط الصفحة')
                    ->helperText('حروف إنجليزي صغيرة وأرقام وشرطة. الصفحة الرئيسية تظهر على "/" دائمًا.')
                    ->prefix('/')
                    ->required()
                    ->maxLength(60)
                    ->regex($pattern)
                    ->notIn(config('preview.reserved_page_slugs'))
                    ->unique(
                        table: 'pages',
                        column: 'slug',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, ?Model $record) => $rule->where(
                            'site_id',
                            $record instanceof Page ? $record->site_id : ($siteId ? $siteId() : null),
                        ),
                    )
                    ->extraAttributes(['dir' => 'ltr']),
                Toggle::make('is_published')->label('منشورة')->default(true)->inline(false),
            ]),
        ];
    }
}
