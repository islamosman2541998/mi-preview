<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Device: string implements HasLabel
{
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Desktop = 'desktop';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mobile => 'موبايل',
            self::Tablet => 'تابلت',
            self::Desktop => 'كمبيوتر',
        };
    }
}
