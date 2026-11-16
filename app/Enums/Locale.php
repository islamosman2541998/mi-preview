<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Locale: string implements HasLabel
{
    case Ar = 'ar';
    case En = 'en';

    public static function default(): self
    {
        return self::from(config('preview.default_locale', 'ar'));
    }

    public function dir(): string
    {
        return $this === self::Ar ? 'rtl' : 'ltr';
    }

    public function other(): self
    {
        return $this === self::Ar ? self::En : self::Ar;
    }

    /** URL prefix: Arabic is unprefixed, English lives under /en. */
    public function prefix(): string
    {
        return $this === self::default() ? '' : '/'.$this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Ar => 'العربية',
            self::En => 'English',
        };
    }
}
