<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Section types shared by all templates. Each template provides
 * resources/views/templates/{key}/sections/{type}.blade.php.
 */
enum SectionType: string implements HasLabel
{
    case Hero = 'hero';
    case About = 'about';
    case Services = 'services';
    case Projects = 'projects';
    case Gallery = 'gallery';
    case Stats = 'stats';
    case Testimonials = 'testimonials';
    case Clients = 'clients';
    case Faq = 'faq';
    case Cta = 'cta';
    case Contact = 'contact';
    case Map = 'map';
    case Menu = 'menu';                     // restaurants
    case Booking = 'booking';               // clinics
    case Products = 'products';             // retail
    case Certifications = 'certifications'; // contracting / fire

    public function getLabel(): string
    {
        return match ($this) {
            self::Hero => 'الواجهة الرئيسية',
            self::About => 'من نحن',
            self::Services => 'الخدمات',
            self::Projects => 'المشاريع',
            self::Gallery => 'معرض الصور',
            self::Stats => 'الأرقام',
            self::Testimonials => 'آراء العملاء',
            self::Clients => 'العملاء',
            self::Faq => 'الأسئلة الشائعة',
            self::Cta => 'دعوة للتواصل',
            self::Contact => 'التواصل',
            self::Map => 'الخريطة',
            self::Menu => 'قائمة الطعام',
            self::Booking => 'الحجز',
            self::Products => 'المنتجات',
            self::Certifications => 'الشهادات والاعتمادات',
        };
    }
}
