<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Tracking\OwnerCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * "Open as owner" (admin only):
 *  1. sets the owner cookie on the parent domain (.mius.it.com), then
 *  2. bounces through a short-lived signed URL on the preview host that sets
 *     a host-only copy — covers browsers that refuse Domain=.localhost locally.
 * Visits from this browser are never recorded and never notify.
 */
class OpenAsOwnerController extends Controller
{
    public function __invoke(Request $request, Site $site): RedirectResponse
    {
        $signed = URL::temporarySignedRoute('preview.owner', now()->addMinutes(5), ['subdomain' => $site->slug]);

        return redirect()->away($signed)->withCookie(OwnerCookie::forParentDomain($request));
    }
}
