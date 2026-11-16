<?php

namespace App\Tracking;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * "Open as owner": an encrypted cookie that stops the browser from being
 * tracked. Set on the parent domain from the admin, and host-only on the
 * preview itself (browsers may refuse Domain=.localhost cookies locally).
 */
class OwnerCookie
{
    public static function name(): string
    {
        return config('preview.owner_cookie.name', 'mi_owner');
    }

    public static function isOwner(Request $request): bool
    {
        return $request->cookie(self::name()) === '1';
    }

    public static function make(Request $request, ?string $domain): Cookie
    {
        return cookie(
            name: self::name(),
            value: '1',
            minutes: 60 * 24 * (int) config('preview.owner_cookie.days', 365),
            path: '/',
            domain: $domain,
            secure: $request->isSecure(),
            httpOnly: true,
            sameSite: 'lax',
        );
    }

    public static function forParentDomain(Request $request): Cookie
    {
        return self::make($request, config('preview.owner_cookie.domain'));
    }

    public static function forCurrentHost(Request $request): Cookie
    {
        return self::make($request, null);
    }
}
