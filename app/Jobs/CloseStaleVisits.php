<?php

namespace App\Jobs;

use App\Models\Visit;
use App\Tracking\VisitMessages;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Every minute: closes visits with no heartbeat for `stale_seconds` and sends
 * the visit summary exactly once (guarded by summary_sent_at).
 *
 * Deliberately not ShouldQueue: Schedule::job() then runs it inline inside
 * schedule:run, so summaries don't wait for the next queue-worker cycle.
 */
class CloseStaleVisits
{
    use Dispatchable;

    public function handle(): void
    {
        Visit::query()
            ->stale(config('preview.tracking.stale_seconds', 60))
            ->with('site')
            ->orderBy('id')
            ->chunkById(100, function ($visits) {
                foreach ($visits as $visit) {
                    $this->close($visit);
                }
            });
    }

    private function close(Visit $visit): void
    {
        $endedAt = $visit->last_ping_at ?? $visit->started_at;

        // atomic close: a concurrent run (or a resumed visit) can't double-close
        $closed = Visit::query()
            ->whereKey($visit->id)
            ->whereNull('ended_at')
            ->update([
                'ended_at' => $endedAt,
                // no heartbeats at all (quick bounce): fall back to wall-clock time
                'duration_seconds' => $visit->duration_seconds > 0
                    ? $visit->duration_seconds
                    : max(0, $endedAt->diffInSeconds($visit->started_at, true)),
            ]);

        if (! $closed || ! $visit->site?->notify_enabled) {
            return;
        }

        $claimed = Visit::query()
            ->whereKey($visit->id)
            ->whereNull('summary_sent_at')
            ->update(['summary_sent_at' => now()]);

        if ($claimed) {
            SendTelegramMessage::sendSoon(VisitMessages::summary($visit->refresh()));
        }
    }
}
