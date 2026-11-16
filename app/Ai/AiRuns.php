<?php

namespace App\Ai;

use App\Jobs\GenerateSiteContent;
use App\Models\AiRun;
use App\Models\Site;

/** Starts AI runs — at most one active run per site. */
class AiRuns
{
    /** A run still "active" after this long lost its worker. */
    private const STALE_MINUTES = 20;

    public function __construct(private AiProviders $providers) {}

    public function configured(): bool
    {
        return $this->providers->any();
    }

    public function active(Site $site): ?AiRun
    {
        $run = $site->aiRuns()->active()->first();

        if ($run && $run->created_at->lt(now()->subMinutes(self::STALE_MINUTES))) {
            $run->forceFill(['status' => AiRun::FAILED, 'error' => 'توقف التوليد دون أن يكتمل — حاول مرة أخرى.', 'finished_at' => now()])->save();

            return null;
        }

        return $run;
    }

    /**
     * @param  list<int>|null  $sectionIds  null = the whole site
     * @param  array{provider?: ?string, instructions?: ?string, fill_images?: bool, seo?: bool}  $options
     */
    public function start(Site $site, ?array $sectionIds = null, array $options = []): AiRun
    {
        $provider = $this->providers->resolve($options['provider'] ?? null); // throws when nothing is configured

        if ($this->active($site)) {
            throw new AiException('يوجد توليد قيد التنفيذ لهذا الموقع — انتظر حتى ينتهي.');
        }

        $run = $site->aiRuns()->create([
            'section_ids' => $sectionIds === null ? null : array_values(array_map('intval', $sectionIds)),
            'options' => array_filter([
                'provider' => $provider->key(),
                'instructions' => filled($options['instructions'] ?? null) ? trim($options['instructions']) : null,
                'fill_images' => (bool) ($options['fill_images'] ?? false),
                'seo' => (bool) ($options['seo'] ?? false),
            ], fn ($v) => $v !== null),
        ]);

        GenerateSiteContent::dispatch($run->id);

        return $run;
    }
}
