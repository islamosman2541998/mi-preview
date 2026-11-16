<?php

namespace App\Jobs;

use App\Ai\AiException;
use App\Ai\ContentGenerator;
use App\Models\AiRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued AI generation. On the shared host the queue runs from cron every minute
 * (queue:work --stop-when-empty), so a run starts within about a minute.
 */
class GenerateSiteContent implements ShouldQueue
{
    use Queueable;

    /** One attempt: a retry would bill a second generation. */
    public int $tries = 1;

    /** Whole-site generations stream for a few minutes. */
    public int $timeout = 900;

    public function __construct(public int $runId) {}

    public function handle(ContentGenerator $generator): void
    {
        $run = AiRun::find($this->runId);

        if (! $run || ! $run->isActive()) {
            return;
        }

        try {
            $generator->run($run);
        } catch (AiException $e) {
            $this->fail_($run, $e->getMessage());
        } catch (Throwable $e) {
            Log::error('AI generation failed', ['run' => $run->id, 'exception' => $e]);
            $this->fail_($run, 'حدث خطأ غير متوقع أثناء التوليد — التفاصيل في سجل التطبيق.');
        }
    }

    /** The worker killed the job (timeout) — never leave the run spinning. */
    public function failed(?Throwable $e): void
    {
        if ($run = AiRun::find($this->runId)) {
            $run->isActive() && $this->fail_($run, 'توقف التوليد قبل أن يكتمل (انتهت المهلة) — حاول مرة أخرى.');
        }
    }

    private function fail_(AiRun $run, string $message): void
    {
        $run->forceFill(['status' => AiRun::FAILED, 'error' => $message, 'stage' => null, 'finished_at' => now()])->save();
    }
}
