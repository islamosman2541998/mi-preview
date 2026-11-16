<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Visitor network context behind Cloudflare.
 *
 * - IP: CF-Connecting-IP is believed only when the TCP peer is a Cloudflare
 *   edge, otherwise anyone could spoof it by hitting the origin directly.
 *   If Apache's mod_remoteip already rewrote REMOTE_ADDR to the visitor,
 *   $request->ip() is already correct, so the fallback covers that too.
 * - Country: CF-IPCountry is accepted whenever it is a valid 2-letter code.
 *   It is analytics only (a spoofer can only lie about themselves), and it
 *   must still work when mod_remoteip hides the Cloudflare peer.
 */
class ClientContext
{
    public function __construct(private Request $request) {}

    public static function from(Request $request): self
    {
        return new self($request);
    }

    public function isFromCloudflare(): bool
    {
        $peer = $this->request->server->get('REMOTE_ADDR');

        return is_string($peer) && IpUtils::checkIp($peer, config('preview.cloudflare_ips', []));
    }

    public function ip(): ?string
    {
        $cfIp = $this->request->headers->get('CF-Connecting-IP');

        if ($cfIp && $this->isFromCloudflare() && filter_var($cfIp, FILTER_VALIDATE_IP)) {
            return $cfIp;
        }

        return $this->request->ip();
    }

    public function ipHash(): ?string
    {
        $ip = $this->ip();

        return $ip ? hash('sha256', $ip.config('app.key')) : null;
    }

    public function country(): ?string
    {
        $country = strtoupper((string) $this->request->headers->get('CF-IPCountry'));

        // XX = unknown, T1 = Tor (Cloudflare special values)
        if (! preg_match('/^[A-Z]{2}$/', $country) || in_array($country, ['XX', 'T1'], true)) {
            return null;
        }

        return $country;
    }
}
