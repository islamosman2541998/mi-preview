<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Ai\AiException;
use App\Ai\AiRuns;
use App\Filament\Resources\Sites\Concerns\HandlesLogoTools;
use App\Filament\Resources\Sites\Schemas\SiteForm;
use App\Filament\Resources\Sites\SiteResource;
use App\Services\SiteBuilder;
use App\Services\Stock\StockPhotos;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Wizard\Step;

/** Wizard: basic info → contact → logo & colours → template → pages → create. */
class CreateSite extends CreateRecord
{
    use HandlesLogoTools;
    use HasWizard;

    protected static string $resource = SiteResource::class;

    protected bool $buildPages = true;

    protected bool $generateContent = false;

    protected function getSteps(): array
    {
        return [
            Step::make('البيانات الأساسية')->icon('heroicon-o-building-office')->schema(SiteForm::basic()),
            Step::make('التواصل')->icon('heroicon-o-phone')->schema(SiteForm::contact()),
            Step::make('الشعار والألوان')->icon('heroicon-o-swatch')->schema(SiteForm::branding()),
            Step::make('القالب')->icon('heroicon-o-rectangle-group')->schema([...SiteForm::template(), ...SiteForm::settings()]),
            Step::make('الصفحات')->icon('heroicon-o-document-duplicate')->schema(SiteForm::pagesPreview()),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->buildPages = (bool) ($data['build_pages'] ?? true);
        $this->generateContent = $this->buildPages && (bool) ($data['ai_generate'] ?? false);
        unset($data['build_pages'], $data['ai_generate']);

        $data['socials'] = array_filter($data['socials'] ?? []) ?: null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $builder = app(SiteBuilder::class);

        $this->buildPages
            ? $builder->build($this->record)
            : $builder->generateNav($this->record);

        if ($this->generateContent) {
            try {
                app(AiRuns::class)->start($this->record, null, [
                    'fill_images' => app(StockPhotos::class)->configured() !== [],
                    'seo' => true,
                ]);
            } catch (AiException) {
                // not configured / already running: the site is still created with default content
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return SiteResource::getUrl('edit', ['record' => $this->record]);
    }
}
