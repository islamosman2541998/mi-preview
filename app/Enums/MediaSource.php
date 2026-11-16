<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MediaSource: string implements HasLabel
{
    case Upload = 'upload';
    case Pexels = 'pexels';
    case Pixabay = 'pixabay';
    case Facebook = 'facebook';
    case Instagram = 'instagram';

    public function getLabel(): string
    {
        return match ($this) {
            self::Upload => 'رفع يدوي',
            self::Pexels => 'Pexels',
            self::Pixabay => 'Pixabay',
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
        };
    }
}
