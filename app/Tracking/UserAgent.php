<?php

namespace App\Tracking;

use App\Enums\Device;

class UserAgent
{
    public static function isBot(?string $ua): bool
    {
        $ua = trim((string) $ua);

        return $ua === '' || preg_match(config('preview.tracking.bot_pattern'), $ua) === 1;
    }

    public static function device(?string $ua): Device
    {
        $ua = (string) $ua;

        return match (true) {
            (bool) preg_match('/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/i', $ua) => Device::Tablet,
            (bool) preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile/i', $ua) => Device::Mobile,
            default => Device::Desktop,
        };
    }
}
