<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Preview-site slug: lowercase a-z0-9-, 3–40 chars, no leading/trailing
 * hyphen, not reserved (infrastructure + existing project subdomains).
 */
class SiteSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValidFormat($value)) {
            $fail('الرابط لازم يكون من 3 إلى 40 حرف: حروف إنجليزي صغيرة وأرقام وشرطة (-)، ومايبدأش أو يخلصش بشرطة.');

            return;
        }

        if (self::isReserved($value)) {
            $fail('الاسم ده محجوز ومينفعش يتستخدم كرابط موقع.');
        }
    }

    public static function isValidFormat(string $slug): bool
    {
        $length = strlen($slug);

        return $length >= config('preview.slug.min', 3)
            && $length <= config('preview.slug.max', 40)
            && preg_match(config('preview.slug.pattern'), $slug) === 1;
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::reserved(), true);
    }

    /** @return list<string> */
    public static function reserved(): array
    {
        return array_values(array_unique([
            ...config('preview.reserved_slugs', []),
            config('preview.admin_subdomain'),
        ]));
    }

    public static function passes(string $slug): bool
    {
        return self::isValidFormat($slug) && ! self::isReserved($slug);
    }
}
