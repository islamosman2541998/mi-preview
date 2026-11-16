<?php

namespace App\Support;

use App\Models\Site;

/** MI Web Development — the agency behind every preview (demo bar). */
class Brand
{
    public static function name(): string
    {
        return (string) config('preview.brand_name');
    }

    /** "I want this website" link to the agency's WhatsApp, prefilled with the slug. */
    public static function whatsappLink(Site $site): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) config('preview.brand_whatsapp'));

        if ($digits === '') {
            return null;
        }

        $text = __('preview.demo.message', [
            'company' => $site->name(),
            'slug' => $site->slug,
            'url' => $site->url(),
        ]);

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }
}
