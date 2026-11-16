<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Site;
use App\Services\Stock\StockPhotos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/** Data for the admin image picker modal (see resources/js/admin.js). */
class MediaPickerController extends Controller
{
    public function stock(Request $request, string $provider, StockPhotos $stock): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'orientation' => ['nullable', 'in:landscape,portrait,square'],
        ]);

        try {
            return response()->json($stock->get($provider)->search($data['q'], (int) ($data['page'] ?? 1), $data['orientation'] ?? null));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function library(Site $site): JsonResponse
    {
        return response()->json([
            'media' => $site->media()->latest('id')->limit(200)->get()->map(fn (Media $m) => [
                'id' => $m->id,
                'thumb' => $m->url(),
                'alt' => $m->alt_ar ?: $m->alt_en,
                'source' => $m->source->value,
                'credit' => $m->credit,
            ]),
        ]);
    }
}
