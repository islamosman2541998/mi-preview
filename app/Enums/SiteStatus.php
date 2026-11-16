<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SiteStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Won = 'won';
    case Lost = 'lost';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Sent => 'تم الإرسال',
            self::Viewed => 'تمت المشاهدة',
            self::Won => 'تم التعاقد',
            self::Lost => 'لم يتم',
            self::Archived => 'مؤرشف',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Viewed => 'warning',
            self::Won => 'success',
            self::Lost => 'danger',
            self::Archived => 'gray',
        };
    }
}
