<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Tracking\OwnerCookie;
use App\Tracking\UserAgent;
use App\Tracking\VisitRecorder;
use App\Tracking\VisitToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Preview-host tracking endpoints used by public/js/t.js.
 *   POST /_t/start   → { track, token, interval }
 *   POST /_t/ping    → 204 | 410 (visit gone/closed → client restarts)
 *   POST /_t/event   → 204
 *   POST /_t/end     → 204 (sendBeacon flush; the visit closes when pings stop)
 */
class TrackingController extends Controller
{
    public function __construct(private VisitRecorder $recorder) {}

    public function start(Request $request, Site $site): JsonResponse
    {
        $data = $request->validate([
            'uuid' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9-]{8,64}$/'],
            'path' => ['nullable', 'string', 'max:512'],
            'referrer' => ['nullable', 'string', 'max:1024'],
        ]);

        if (OwnerCookie::isOwner($request) || UserAgent::isBot($request->userAgent())) {
            return response()->json(['track' => false]);
        }

        [$visit] = $this->recorder->start($site, $request, $data);

        return response()->json([
            'track' => true,
            'token' => VisitToken::make($visit),
            'interval' => (int) config('preview.tracking.ping_seconds', 15),
        ]);
    }

    public function ping(Request $request, Site $site): Response
    {
        $data = $this->validateFlush($request);
        $visit = VisitToken::resolve($data['token'], $site);

        if (! $visit || ! $visit->isOpen()) {
            return response()->noContent(410);
        }

        $this->recorder->ping($visit, $data);

        return response()->noContent();
    }

    public function end(Request $request, Site $site): Response
    {
        $data = $this->validateFlush($request);
        $visit = VisitToken::resolve($data['token'], $site);

        if ($visit && $visit->isOpen()) {
            $this->recorder->ping($visit, $data);
        }

        return response()->noContent();
    }

    public function event(Request $request, Site $site): Response
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'type' => ['required', 'in:whatsapp,phone,cta'],
            'label' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9_-]*$/'],
            'path' => ['nullable', 'string', 'max:512'],
        ]);

        $visit = VisitToken::resolve($data['token'], $site);

        if (! $visit) {
            return response()->noContent(410);
        }

        $this->recorder->click($visit, $data['type'], $data['label'] ?? null, $data['path'] ?? null);

        return response()->noContent();
    }

    /** Second leg of "Open as owner": host-only cookie on the preview itself. */
    public function owner(Request $request, Site $site): RedirectResponse
    {
        return redirect('/')->withCookie(OwnerCookie::forCurrentHost($request));
    }

    private function validateFlush(Request $request): array
    {
        return $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'active' => ['nullable', 'integer', 'min:0', 'max:120'],
            'sections' => ['nullable', 'array', 'max:30'],
            'sections.*' => ['integer', 'min:0', 'max:120'],
            'depth' => ['nullable', 'integer', 'min:0', 'max:100'],
            'path' => ['nullable', 'string', 'max:512'],
        ]);
    }
}
