<?php

namespace App\Tracking;

use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Enums\VisitEventType;
use App\Jobs\SendTelegramMessage;
use App\Models\Site;
use App\Models\Visit;
use App\Models\VisitAlert;
use App\Models\VisitEvent;
use App\Support\ClientContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitRecorder
{
    /**
     * Starts a visit, or resumes the visitor's recent one (navigating to
     * another page, a new tab, or coming back within the resume window).
     *
     * @return array{0: Visit, 1: bool} [visit, isNew]
     */
    public function start(Site $site, Request $request, array $data): array
    {
        $now = now();
        $path = $this->cleanPath($data['path'] ?? '/');

        [$visit, $isNew] = DB::transaction(function () use ($site, $request, $data, $now, $path) {
            $recent = Visit::query()
                ->where('site_id', $site->id)
                ->where('visitor_uuid', $data['uuid'])
                ->where(fn ($q) => $q
                    ->where('last_ping_at', '>=', $now->copy()->subMinutes(config('preview.tracking.resume_minutes')))
                    ->orWhere('started_at', '>=', $now->copy()->subMinutes(config('preview.tracking.resume_minutes'))))
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($recent) {
                $recent->forceFill([
                    'last_ping_at' => $now,
                    // reopened after it was closed: a fresh summary goes out when it ends again
                    'ended_at' => null,
                    'summary_sent_at' => $recent->ended_at ? null : $recent->summary_sent_at,
                ])->save();

                $this->addEvent($recent, VisitEventType::PageView, ['path' => $path]);
                $site->forceFill(['last_viewed_at' => $now])->save();

                return [$recent, false];
            }

            $ctx = ClientContext::from($request);
            $ua = (string) $request->userAgent();

            $visit = $site->visits()->create([
                'visitor_uuid' => $data['uuid'],
                'ip_hash' => $ctx->ipHash(),
                'user_agent' => mb_substr($ua, 0, 512),
                'device' => UserAgent::device($ua),
                'country' => $ctx->country(),
                'referrer' => $this->externalReferrer($data['referrer'] ?? null, $request),
                'landing_path' => $path,
                'started_at' => $now,
                'last_ping_at' => $now,
                'is_return' => Visit::query()->where('site_id', $site->id)->where('visitor_uuid', $data['uuid'])->exists(),
            ]);

            $this->addEvent($visit, VisitEventType::PageView, ['path' => $path]);

            $site->forceFill([
                'views_count' => $site->views_count + 1,
                'first_viewed_at' => $site->first_viewed_at ?? $now,
                'last_viewed_at' => $now,
                'status' => in_array($site->status, [SiteStatus::Draft, SiteStatus::Sent], true)
                    ? SiteStatus::Viewed
                    : $site->status,
            ])->save();

            return [$visit, true];
        });

        if ($isNew) {
            $this->notifyStarted($visit);
        }

        return [$visit, $isNew];
    }

    /** Heartbeat / final flush: active seconds, per-section dwell, scroll depth. */
    public function ping(Visit $visit, array $data): void
    {
        $active = min(max((int) ($data['active'] ?? 0), 0), 60);
        $path = $this->cleanPath($data['path'] ?? $visit->landing_path ?? '/');

        DB::transaction(function () use ($visit, $data, $active, $path) {
            $visit->forceFill([
                'last_ping_at' => now(),
                'duration_seconds' => $visit->duration_seconds + $active,
            ])->save();

            $rows = [];
            foreach (array_slice((array) ($data['sections'] ?? []), 0, 30, true) as $key => $seconds) {
                $seconds = min(max((int) $seconds, 0), 60);
                if ($seconds > 0 && SectionType::tryFrom((string) $key)) {
                    $rows[] = $this->eventRow($visit, VisitEventType::SectionView, ['section' => $key, 'seconds' => $seconds, 'path' => $path]);
                }
            }

            $depth = (int) ($data['depth'] ?? 0);
            if (in_array($depth, [25, 50, 75, 100], true)) {
                $rows[] = $this->eventRow($visit, VisitEventType::ScrollDepth, ['depth' => $depth, 'path' => $path]);
            }

            if ($rows) {
                VisitEvent::insert($rows);
            }
        });
    }

    /** whatsapp / phone / cta click → event + one high-intent alert per visit per type. */
    public function click(Visit $visit, string $type, ?string $label, ?string $path): void
    {
        $eventType = match ($type) {
            'whatsapp' => VisitEventType::WhatsappClick,
            'phone' => VisitEventType::PhoneClick,
            default => VisitEventType::CtaClick,
        };

        $this->addEvent($visit, $eventType, array_filter([
            'label' => $label,
            'path' => $path ? $this->cleanPath($path) : null,
        ]));

        $visit->forceFill(['last_ping_at' => now()])->save();

        // "I want this website" in the demo bar is its own (most important) signal
        $alertType = $label === 'demo_bar' ? 'demo_bar' : $type;

        if ($this->claimAlert($visit, $alertType)) {
            $this->send($visit, VisitMessages::highIntent($visit, $alertType));
        }
    }

    // ------------------------------------------------------------- internals

    private function notifyStarted(Visit $visit): void
    {
        // a return visit is itself a high-intent signal: one 🔥 message instead of two
        if ($visit->is_return) {
            $this->claimAlert($visit, 'return');
        }

        $this->send($visit, VisitMessages::started($visit));
    }

    /** Atomic: true only for the first caller per (visit, type). */
    private function claimAlert(Visit $visit, string $type): bool
    {
        return VisitAlert::query()->insertOrIgnore([
            'visit_id' => $visit->id,
            'type' => $type,
            'sent_at' => now(),
        ]) === 1;
    }

    private function send(Visit $visit, string $text): void
    {
        if ($visit->site->notify_enabled) {
            SendTelegramMessage::sendSoon($text);
        }
    }

    private function addEvent(Visit $visit, VisitEventType $type, array $payload): void
    {
        VisitEvent::insert($this->eventRow($visit, $type, $payload));
    }

    private function eventRow(Visit $visit, VisitEventType $type, array $payload): array
    {
        return [
            'visit_id' => $visit->id,
            'type' => $type->value,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'occurred_at' => now(),
        ];
    }

    private function cleanPath(?string $path): string
    {
        $path = '/'.ltrim((string) parse_url((string) $path, PHP_URL_PATH), '/');

        return mb_substr($path, 0, 255);
    }

    /** Referrer only when it comes from outside the preview itself. */
    private function externalReferrer(?string $referrer, Request $request): ?string
    {
        if (blank($referrer) || ! filter_var($referrer, FILTER_VALIDATE_URL)) {
            return null;
        }

        return parse_url($referrer, PHP_URL_HOST) === $request->getHost() ? null : mb_substr($referrer, 0, 512);
    }
}
