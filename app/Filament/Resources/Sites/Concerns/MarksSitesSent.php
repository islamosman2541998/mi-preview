<?php

namespace App\Filament\Resources\Sites\Concerns;

use App\Enums\SiteStatus;
use App\Models\Site;

/** Called from the browser after the pitch message was copied. */
trait MarksSitesSent
{
    public function markSiteSent(int $siteId): void
    {
        $site = Site::find($siteId);

        if (! $site) {
            return;
        }

        $site->forceFill(['sent_at' => $site->sent_at ?? now()]);

        if ($site->status === SiteStatus::Draft) {
            $site->status = SiteStatus::Sent;
        }

        $site->save();
    }
}
