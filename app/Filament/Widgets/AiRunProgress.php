<?php

namespace App\Filament\Widgets;

use App\Ai\AiRuns;
use App\Models\AiRun;
use App\Models\Page;
use App\Models\Site;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

/**
 * Status of the latest AI run for the site being edited (site or page screen).
 * Polls while the run is queued/running; shows the result for a while after.
 */
class AiRunProgress extends Widget
{
    protected string $view = 'filament.widgets.ai-run-progress';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false; // only on the site/page screens

    protected static bool $isLazy = false;

    public ?Model $record = null;

    /** Run id seen as active on the previous poll — to notice it finishing. */
    public ?int $watching = null;

    public bool $dismissed = false;

    public function site(): ?Site
    {
        return match (true) {
            $this->record instanceof Site => $this->record,
            $this->record instanceof Page => $this->record->site,
            default => null,
        };
    }

    public function run(): ?AiRun
    {
        $site = $this->site();

        if (! $site) {
            return null;
        }

        app(AiRuns::class)->active($site); // expires a run whose worker died

        $run = $site->aiRuns()->first();

        // finished runs stay visible for 15 minutes (or until dismissed)
        if (! $run || (! $run->isActive() && ($this->dismissed || $run->finished_at?->lt(now()->subMinutes(15))))) {
            return null;
        }

        return $run;
    }

    #[On('ai-run-started')]
    public function runStarted(): void
    {
        $this->dismissed = false; // re-render picks the new run up and starts polling
    }

    public function dismiss(): void
    {
        $this->dismissed = true;
    }

    protected function getViewData(): array
    {
        $run = $this->run();
        $justFinished = $run && ! $run->isActive() && $this->watching === $run->id;
        $this->watching = $run?->isActive() ? $run->id : null;

        return [
            'run' => $run,
            'justFinished' => $justFinished,
            'queueHint' => config('queue.default') !== 'sync',
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
