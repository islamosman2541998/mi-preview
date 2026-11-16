<?php

namespace App\Tracking;

use App\Enums\SectionType;
use App\Enums\VisitEventType;
use App\Models\Visit;
use Illuminate\Support\Facades\Route;

/**
 * Arabic Telegram messages (HTML parse mode). Pure string building so the
 * wording can be tested without sending anything.
 */
class VisitMessages
{
    public static function started(Visit $visit): string
    {
        $company = self::company($visit);

        $lines = [
            $visit->is_return
                ? "🔥 <b>{$company}</b> رجع فتح الموقع تاني"
                : "🟢 <b>{$company}</b> فتح الموقع الآن",
            self::whereLine($visit),
            $visit->is_return
                ? '🔁 زيارة متكررة (رقم '.self::visitNumber($visit).')'
                : '🆕 أول زيارة',
        ];

        if (filled($visit->referrer)) {
            $lines[] = '↪️ جاي من: '.e(parse_url($visit->referrer, PHP_URL_HOST) ?: $visit->referrer);
        }

        $lines[] = self::link($visit);

        return implode("\n", array_filter($lines));
    }

    public static function highIntent(Visit $visit, string $alertType): string
    {
        $company = self::company($visit);

        $action = match ($alertType) {
            'demo_bar' => 'ضغط «أريد هذا الموقع» 🎯',
            'whatsapp' => 'ضغط على واتساب الشركة',
            'phone' => 'ضغط على رقم الاتصال',
            default => 'ضغط على زر تواصل',
        };

        return implode("\n", array_filter([
            "🔥 <b>{$company}</b> {$action}",
            self::whereLine($visit),
            self::link($visit),
        ]));
    }

    public static function summary(Visit $visit): string
    {
        $visit->loadMissing(['site.pages', 'events']);
        $company = self::company($visit);
        $events = $visit->events;

        // pages in visit order, consecutive duplicates collapsed
        $titles = $visit->site->pages->mapWithKeys(fn ($p) => [$p->slug => $p->title('ar')]);
        $home = $visit->site->pages->firstWhere('is_home', true)?->title('ar') ?? 'الرئيسية';
        $pages = $events->where('type', VisitEventType::PageView)
            ->map(function ($e) use ($titles, $home) {
                $slug = trim(preg_replace('#^/?en(/|$)#', '', (string) ($e->payload['path'] ?? '/')), '/');

                return $slug === '' ? $home : ($titles[$slug] ?? $slug);
            })
            ->values()
            ->reduce(fn ($carry, $t) => end($carry) === $t ? $carry : [...$carry, $t], []);

        $sections = $events->where('type', VisitEventType::SectionView)
            ->groupBy(fn ($e) => $e->payload['section'] ?? '?')
            ->map(fn ($g) => $g->sum(fn ($e) => (int) ($e->payload['seconds'] ?? 0)))
            ->filter()
            ->sortDesc()
            ->take(5);

        $clicked = fn (VisitEventType $type, ?string $label = null) => $events->contains(
            fn ($e) => $e->type === $type && ($label === null || ($e->payload['label'] ?? null) === $label)
        );

        $lines = [
            "📊 <b>ملخص زيارة {$company}</b>",
            '⏱ المدة: '.self::duration($visit->duration_seconds),
        ];

        if ($pages) {
            $lines[] = '📄 الصفحات: '.e(implode(' ← ', $pages));
        }

        if ($sections->isNotEmpty()) {
            $lines[] = '👀 أكثر الأقسام مشاهدة:';
            $rank = 1;
            foreach ($sections as $key => $seconds) {
                $label = SectionType::tryFrom($key)?->getLabel() ?? e($key);
                $lines[] = ($rank++).". {$label} — ".self::duration($seconds);
            }
        }

        $lines[] = '💬 واتساب: '.($clicked(VisitEventType::WhatsappClick) ? '✅' : '—')
            .'   📞 اتصال: '.($clicked(VisitEventType::PhoneClick) ? '✅' : '—')
            .'   🎯 أريد الموقع: '.($clicked(VisitEventType::CtaClick, 'demo_bar') ? '✅' : '—');
        $lines[] = self::whereLine($visit);
        $lines[] = self::link($visit);

        return implode("\n", array_filter($lines));
    }

    // ------------------------------------------------------------------ parts

    private static function company(Visit $visit): string
    {
        return e($visit->site->name('ar'));
    }

    private static function whereLine(Visit $visit): string
    {
        $device = match ($visit->device?->value) {
            'mobile' => '📱 موبايل',
            'tablet' => '📱 تابلت',
            default => '💻 كمبيوتر',
        };

        return $device.($visit->country ? '   '.self::flag($visit->country).' '.$visit->country : '');
    }

    private static function visitNumber(Visit $visit): int
    {
        return Visit::query()
            ->where('site_id', $visit->site_id)
            ->where('visitor_uuid', $visit->visitor_uuid)
            ->where('id', '<=', $visit->id)
            ->count();
    }

    private static function link(Visit $visit): string
    {
        // visit page in the admin once the Filament Sites resource exists, otherwise the preview itself
        $url = Route::has('filament.admin.resources.sites.visits')
            ? route('filament.admin.resources.sites.visits', ['record' => $visit->site_id, 'visit' => $visit->id])
            : $visit->site->url();

        return '🔗 <a href="'.e($url).'">'.(Route::has('filament.admin.resources.sites.visits') ? 'تفاصيل الزيارة' : 'فتح الموقع').'</a>';
    }

    public static function duration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;

        return match (true) {
            $m === 0 => "{$s} ث",
            $s === 0 => "{$m} د",
            default => "{$m} د {$s} ث",
        };
    }

    public static function flag(string $country): string
    {
        $country = strtoupper($country);

        if (! preg_match('/^[A-Z]{2}$/', $country)) {
            return '';
        }

        return mb_chr(0x1F1E6 + ord($country[0]) - 65).mb_chr(0x1F1E6 + ord($country[1]) - 65);
    }
}
