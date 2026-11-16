<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tracking endpoints are hit by sendBeacon (no Accept header) on session-less
 * preview hosts: validation errors must come back as JSON, never as a
 * redirect that needs a session.
 */
class ForceJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
