<?php

namespace App\Support;

/**
 * The fixed set of line icons a service can carry. The AI picks a key from this
 * list (never free-form), the admin can change it, and the templates draw it with
 * <x-preview.icon>. Anything unknown resolves to DEFAULT.
 */
class ServiceIcons
{
    public const DEFAULT = 'sparkle';

    /** Order is part of the AI schema — append only. */
    public const KEYS = [
        'building', 'crane', 'helmet', 'road', 'tools', 'blueprint', 'shield', 'flame', 'truck', 'box',
        'stethoscope', 'tooth', 'heart', 'scissors', 'sparkle', 'utensils', 'coffee', 'cart', 'tag', 'home',
        'key', 'chart', 'users', 'clock', 'wrench', 'bolt', 'drop', 'leaf', 'phone-support', 'star',
    ];

    private const LABELS = [
        'building' => 'مبنى', 'crane' => 'رافعة', 'helmet' => 'خوذة / سلامة', 'road' => 'طريق', 'tools' => 'أدوات',
        'blueprint' => 'مخطط هندسي', 'shield' => 'حماية', 'flame' => 'حريق', 'truck' => 'شاحنة / توريد', 'box' => 'صندوق / منتج',
        'stethoscope' => 'طبي', 'tooth' => 'أسنان', 'heart' => 'رعاية', 'scissors' => 'مقص / تجميل', 'sparkle' => 'لمعة (افتراضي)',
        'utensils' => 'مطعم', 'coffee' => 'قهوة', 'cart' => 'عربة تسوق', 'tag' => 'سعر / عرض', 'home' => 'منزل',
        'key' => 'مفتاح / تسليم', 'chart' => 'إحصاءات / إدارة', 'users' => 'فريق', 'clock' => 'وقت / مواعيد', 'wrench' => 'صيانة',
        'bolt' => 'كهرباء', 'drop' => 'مياه / سباكة', 'leaf' => 'طبيعي / زراعة', 'phone-support' => 'دعم', 'star' => 'جودة',
    ];

    public static function resolve(?string $key): string
    {
        return in_array($key, self::KEYS, true) ? $key : self::DEFAULT;
    }

    /** @return array<string, string> key → Arabic label, for the admin select */
    public static function options(): array
    {
        $out = [];
        foreach (self::KEYS as $key) {
            $out[$key] = self::LABELS[$key] ?? $key;
        }

        return $out;
    }
}
