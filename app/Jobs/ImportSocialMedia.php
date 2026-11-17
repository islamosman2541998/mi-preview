<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\SocialProfileImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Downloads imported post images after site creation so the admin request stays fast. */
class ImportSocialMedia implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $siteId) {}

    public function handle(SocialProfileImporter $importer): void
    {
        $site = Site::find($this->siteId);

        if ($site && filled($site->source_data)) {
            $importer->importMediaAndSeed($site);
        }
    }
}
