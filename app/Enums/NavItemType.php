<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavItemType: string implements HasLabel
{
    case Page = 'page';         // target = page slug
    case Anchor = 'anchor';     // target = section anchor on the home page ("services")
    case External = 'external'; // target = absolute URL
    case Whatsapp = 'whatsapp'; // target = number (empty → site whatsapp)
    case Phone = 'phone';       // target = number (empty → site phone)

    public function getLabel(): string
    {
        return match ($this) {
            self::Page => 'صفحة',
            self::Anchor => 'قسم في الصفحة الرئيسية',
            self::External => 'رابط خارجي',
            self::Whatsapp => 'واتساب',
            self::Phone => 'اتصال',
        };
    }
}
