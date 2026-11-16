<?php

namespace App\Filament\Support;

use App\Models\Site;

/** Arabic WhatsApp pitch message sent to the prospect with the preview link. */
class Pitch
{
    public static function message(Site $site): string
    {
        $company = $site->name('ar');
        $brand = config('preview.brand_name');

        return implode("\n", [
            'السلام عليكم ورحمة الله،',
            '',
            "جهزنا لحضرتك تصور مبدئي لموقع *{$company}* — موقع كامل بالعربي والإنجليزي، متجاوب مع الموبايل، ومعمول مخصوص لنشاطكم:",
            $site->url(),
            '',
            'تقدر تتصفحه براحتك، ولو عجبك نقدر نجهزه بالمحتوى والصور الخاصة بيكم ونطلقه على دومين باسم الشركة.',
            '',
            "{$brand}",
        ]);
    }
}
