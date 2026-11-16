<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VisitEventType: string implements HasLabel
{
    case PageView = 'page_view';
    case SectionView = 'section_view';
    case WhatsappClick = 'whatsapp_click';
    case PhoneClick = 'phone_click';
    case CtaClick = 'cta_click';
    case ScrollDepth = 'scroll_depth';

    public function getLabel(): string
    {
        return match ($this) {
            self::PageView => 'فتح صفحة',
            self::SectionView => 'مشاهدة قسم',
            self::WhatsappClick => 'ضغط واتساب',
            self::PhoneClick => 'ضغط اتصال',
            self::CtaClick => 'ضغط زر',
            self::ScrollDepth => 'عمق التمرير',
        };
    }

    public function isHighIntent(): bool
    {
        return in_array($this, [self::WhatsappClick, self::PhoneClick, self::CtaClick], true);
    }
}
