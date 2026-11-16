<?php

namespace App\Tracking;

use App\Models\Site;
use App\Models\Visit;

/**
 * Short signed visit token "{id}.{hmac}" returned by /_t/start and required by
 * ping/event/end. Bound to the site, so a token from one preview can't be
 * replayed against another.
 */
class VisitToken
{
    public static function make(Visit $visit): string
    {
        return $visit->id.'.'.self::signature($visit->id, $visit->site_id);
    }

    public static function resolve(?string $token, Site $site): ?Visit
    {
        if (! is_string($token) || ! preg_match('/^(\d{1,12})\.([a-f0-9]{32})$/', $token, $m)) {
            return null;
        }

        if (! hash_equals(self::signature((int) $m[1], $site->id), $m[2])) {
            return null;
        }

        return Visit::query()->where('site_id', $site->id)->find((int) $m[1]);
    }

    private static function signature(int $visitId, int $siteId): string
    {
        return substr(hash_hmac('sha256', "visit:{$visitId}:site:{$siteId}", (string) config('app.key')), 0, 32);
    }
}
