<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Enums\SectionType;
use App\Enums\VisitEventType;
use App\Filament\Resources\Sites\SiteResource;
use App\Models\Site;
use App\Models\Visit;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/** Timeline of a site's visits with events and section dwell bars. */
class SiteVisits extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.sites.visits';

    /** Visit opened from a Telegram alert link (?visit=ID). */
    #[Url]
    public ?int $visit = null;

    public int $limit = 25;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return 'زيارات '.$this->record->company_name_ar;
    }

    public function getBreadcrumb(): string
    {
        return 'الزيارات';
    }

    public function loadMore(): void
    {
        $this->limit += 25;
    }

    protected function getViewData(): array
    {
        /** @var Site $site */
        $site = $this->record;
        $total = $site->visits()->count();

        $visits = $site->visits()
            ->with('events')
            ->latest('started_at')
            ->limit($this->limit)
            ->get();

        // a deep-linked visit outside the current page is still shown on top
        if ($this->visit && ! $visits->contains('id', $this->visit) && ($linked = $site->visits()->with('events')->find($this->visit))) {
            $visits->prepend($linked);
        }

        $titles = $site->pages()->pluck('title_ar', 'slug');
        $home = $site->homePage?->title_ar ?? 'الرئيسية';

        return [
            'site' => $site,
            'total' => $total,
            'hasMore' => $total > $this->limit,
            'summary' => $this->summary($site),
            'visits' => $visits->map(fn (Visit $v) => $this->present($v, $titles, $home)),
        ];
    }

    private function summary(Site $site): array
    {
        $visits = $site->visits();

        return [
            'visits' => $visits->count(),
            'visitors' => $site->visits()->distinct('visitor_uuid')->count('visitor_uuid'),
            'avg' => (int) round((float) $site->visits()->whereNotNull('ended_at')->avg('duration_seconds')),
            'whatsapp' => $site->visits()->whereHas('events', fn ($q) => $q->whereIn('type', [VisitEventType::WhatsappClick, VisitEventType::PhoneClick]))->count(),
        ];
    }

    private function present(Visit $visit, Collection $titles, string $home): array
    {
        $events = $visit->events;
        $pageName = function (?string $path) use ($titles, $home) {
            $slug = trim(preg_replace('#^/?en(/|$)#', '', (string) $path), '/');

            return ($slug === '' ? $home : ($titles[$slug] ?? $slug)).(str_starts_with((string) $path, '/en') ? ' (EN)' : '');
        };

        $dwell = $events->where('type', VisitEventType::SectionView)
            ->groupBy(fn ($e) => $e->payload['section'] ?? '?')
            ->map(fn ($g) => $g->sum(fn ($e) => (int) ($e->payload['seconds'] ?? 0)))
            ->filter()
            ->sortDesc();
        $max = max(1, (int) $dwell->max());

        $timeline = $events
            ->reject(fn ($e) => $e->type === VisitEventType::SectionView)
            ->map(fn ($e) => [
                'time' => $e->occurred_at,
                'type' => $e->type,
                'text' => match ($e->type) {
                    VisitEventType::PageView => 'فتح صفحة: '.$pageName($e->payload['path'] ?? '/'),
                    VisitEventType::ScrollDepth => 'وصل إلى '.($e->payload['depth'] ?? '?').'% من '.$pageName($e->payload['path'] ?? '/'),
                    VisitEventType::WhatsappClick => 'ضغط واتساب'.$this->where($e->payload),
                    VisitEventType::PhoneClick => 'ضغط اتصال'.$this->where($e->payload),
                    VisitEventType::CtaClick => (($e->payload['label'] ?? null) === 'demo_bar' ? 'ضغط «أريد هذا الموقع»' : 'ضغط زر').$this->where($e->payload),
                    default => $e->type->getLabel(),
                },
                'hot' => $e->type->isHighIntent(),
            ])
            ->values();

        return [
            'model' => $visit,
            'highlight' => $visit->id === $this->visit,
            'pages' => $events->where('type', VisitEventType::PageView)->map(fn ($e) => $pageName($e->payload['path'] ?? '/'))
                ->values()->reduce(fn ($c, $t) => end($c) === $t ? $c : [...$c, $t], []),
            'dwell' => $dwell->map(fn ($s, $key) => [
                'label' => SectionType::tryFrom($key)?->getLabel() ?? $key,
                'seconds' => $s,
                'pct' => (int) round($s / $max * 100),
            ])->values(),
            'timeline' => $timeline,
            'whatsapp' => $events->contains(fn ($e) => $e->type === VisitEventType::WhatsappClick),
            'phone' => $events->contains(fn ($e) => $e->type === VisitEventType::PhoneClick),
            'demoBar' => $events->contains(fn ($e) => $e->type === VisitEventType::CtaClick && ($e->payload['label'] ?? null) === 'demo_bar'),
        ];
    }

    private function where(?array $payload): string
    {
        $labels = [
            'hero' => 'الواجهة', 'nav' => 'القائمة', 'float' => 'الزر العائم', 'footer' => 'الفوتر',
            'contact' => 'قسم التواصل', 'contact_list' => 'بيانات التواصل', 'cta' => 'شريط الدعوة', 'utility' => 'الشريط العلوي',
            'booking' => 'الحجز', 'booking_option' => 'خيار حجز', 'demo_bar' => 'الشريط التجريبي',
        ];
        $label = $payload['label'] ?? null;

        return $label ? ' ('.($labels[$label] ?? $label).')' : '';
    }
}
