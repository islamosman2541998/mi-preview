<?php

namespace App\Filament\Widgets;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Sent → viewed → won funnel, plus this week's visits. */
class PipelineStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $sent = Site::whereNotNull('sent_at')->count();
        $viewed = Site::whereNotNull('first_viewed_at')->count();
        $won = Site::where('status', SiteStatus::Won)->count();
        $pct = fn (int $part, int $of) => $of > 0 ? round($part / $of * 100).'%' : '—';

        $weekly = collect(range(6, 0))->map(fn ($d) => Visit::whereDate('started_at', now()->subDays($d))->count())->all();

        return [
            Stat::make('مواقع أُرسلت', number_format($sent))
                ->description(Site::where('status', SiteStatus::Draft)->count().' مسودة')
                ->icon('heroicon-o-paper-airplane'),
            Stat::make('تمت مشاهدتها', number_format($viewed))
                ->description('نسبة الفتح '.$pct($viewed, $sent))
                ->icon('heroicon-o-eye')
                ->color('warning'),
            Stat::make('تم التعاقد', number_format($won))
                ->description('من المشاهدة '.$pct($won, $viewed))
                ->icon('heroicon-o-trophy')
                ->color('success'),
            Stat::make('زيارات آخر 7 أيام', number_format(array_sum($weekly)))
                ->chart($weekly)
                ->icon('heroicon-o-chart-bar')
                ->color('primary'),
        ];
    }
}
