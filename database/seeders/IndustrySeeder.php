<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;

/**
 * Industries with their page/section blueprints (the rules that decide which
 * sections a site gets — the AI only fills content). Idempotent: re-running
 * updates existing rows by key.
 */
class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::industries() as $industry) {
            Industry::updateOrCreate(['key' => $industry['key']], $industry);
        }
    }

    private static function page(string $slug, array $sections, bool $home = false): array
    {
        $titles = [
            'home' => ['ar' => 'الرئيسية', 'en' => 'Home'],
            'about' => ['ar' => 'من نحن', 'en' => 'About'],
            'services' => ['ar' => 'خدماتنا', 'en' => 'Services'],
            'projects' => ['ar' => 'مشاريعنا', 'en' => 'Projects'],
            'products' => ['ar' => 'المنتجات', 'en' => 'Products'],
            'menu' => ['ar' => 'المنيو', 'en' => 'Menu'],
            'contact' => ['ar' => 'تواصل معنا', 'en' => 'Contact'],
        ];

        return array_filter([
            'slug' => $slug,
            'title' => $titles[$slug] ?? ['ar' => $slug, 'en' => $slug],
            'is_home' => $home ?: null,
            'sections' => $sections,
        ], fn ($v) => $v !== null);
    }

    public static function industries(): array
    {
        $contact = self::page('contact', ['contact', 'map']);

        return [
            [
                'key' => 'contracting',
                'name_ar' => 'مقاولات عامة',
                'name_en' => 'General contracting',
                'sections_blueprint' => ['pages' => [
                    self::page('home', ['hero', 'about', 'services', 'stats', 'projects', 'certifications', 'clients', 'cta'], true),
                    self::page('about', ['about', 'stats', 'certifications', 'testimonials']),
                    self::page('services', ['services', 'faq', 'cta']),
                    self::page('projects', ['projects', 'gallery']),
                    $contact,
                ]],
                'stock_keywords' => ['construction site', 'building construction', 'crane', 'civil engineers', 'concrete structure', 'architecture building'],
                'ai_hints' => 'Corporate, confident tone. Emphasise safety, deadlines, licences and classification, project scale, '
                    .'in-house equipment and engineering team. Use concrete numbers (years, projects, square metres).',
                'default_palette' => ['primary' => '#1F3A5F', 'secondary' => '#F2A900', 'accent' => '#D9480F', 'text' => '#1B1F24', 'bg' => '#F5F5F2'],
            ],
            [
                'key' => 'fire_safety',
                'name_ar' => 'أنظمة الإطفاء والإنذار',
                'name_en' => 'Fire protection & alarm systems',
                'sections_blueprint' => ['pages' => [
                    self::page('home', ['hero', 'services', 'stats', 'certifications', 'projects', 'clients', 'cta'], true),
                    self::page('services', ['services', 'faq']),
                    self::page('projects', ['projects', 'gallery']),
                    $contact,
                ]],
                'stock_keywords' => ['fire sprinkler', 'fire extinguisher', 'fire alarm panel', 'firefighting pump room', 'safety inspection'],
                'ai_hints' => 'Technical, trustworthy tone. Mention civil defence approvals, NFPA standards, design/supply/install/maintenance, '
                    .'periodic inspection contracts, response time.',
                'default_palette' => ['primary' => '#B3261E', 'secondary' => '#1E1E1E', 'accent' => '#F5B700', 'text' => '#1A1A1A', 'bg' => '#F7F6F4'],
            ],
            [
                'key' => 'supply',
                'name_ar' => 'توريدات عامة',
                'name_en' => 'General supply & trading',
                'sections_blueprint' => ['pages' => [
                    self::page('home', ['hero', 'about', 'products', 'clients', 'stats', 'cta'], true),
                    self::page('products', ['products', 'faq']),
                    $contact,
                ]],
                'stock_keywords' => ['warehouse', 'logistics', 'industrial supplies', 'delivery truck', 'pallets'],
                'ai_hints' => 'Reliable B2B tone. Emphasise stock availability, fast delivery, brands represented, government and corporate clients.',
                'default_palette' => ['primary' => '#0F4C5C', 'secondary' => '#E36414', 'accent' => '#FB8B24', 'text' => '#1C2321', 'bg' => '#F6F7F5'],
            ],
            [
                'key' => 'clinic',
                'name_ar' => 'عيادات ومراكز طبية',
                'name_en' => 'Clinics & medical centres',
                'sections_blueprint' => ['pages' => [
                    self::page('home', ['hero', 'services', 'about', 'stats', 'testimonials', 'booking', 'faq'], true),
                    self::page('services', ['services', 'booking']),
                    $contact,
                ]],
                'stock_keywords' => ['modern clinic', 'doctor consultation', 'dental clinic', 'medical team', 'clinic reception'],
                'ai_hints' => 'Warm, reassuring, professional tone. Mention doctors\' specialties, modern equipment, easy booking, '
                    .'working hours. Avoid medical claims or guarantees.',
                'default_palette' => ['primary' => '#0F766E', 'secondary' => '#134E4A', 'accent' => '#E9A06B', 'text' => '#1F2933', 'bg' => '#FAFAF7'],
            ],
            [
                'key' => 'restaurant',
                'name_ar' => 'مطاعم وكافيهات',
                'name_en' => 'Restaurants & cafés',
                'sections_blueprint' => ['pages' => [
                    self::page('home', ['hero', 'about', 'menu', 'gallery', 'testimonials', 'cta'], true),
                    self::page('menu', ['menu']),
                    $contact,
                ]],
                'stock_keywords' => ['restaurant interior', 'plated dish', 'chef cooking', 'coffee shop', 'dining table'],
                'ai_hints' => 'Appetising, inviting tone. Signature dishes, ingredients, atmosphere, delivery and reservations.',
                'default_palette' => ['primary' => '#8C2F1B', 'secondary' => '#2B2118', 'accent' => '#D9A441', 'text' => '#2B2118', 'bg' => '#FBF7F0'],
            ],
            [
                'key' => 'beauty',
                'name_ar' => 'تجميل وعطور',
                'name_en' => 'Beauty & perfume',
                'sections_blueprint' => ['pages' => [
                    self::page('home', ['hero', 'services', 'gallery', 'products', 'testimonials', 'booking'], true),
                    self::page('services', ['services', 'faq']),
                    $contact,
                ]],
                'stock_keywords' => ['beauty salon', 'perfume bottle', 'skincare', 'spa treatment', 'cosmetics flatlay'],
                'ai_hints' => 'Elegant, soft, premium tone. Experience, products used, hygiene, personalised care.',
                'default_palette' => ['primary' => '#7A4E5E', 'secondary' => '#2E2A2B', 'accent' => '#C9A27E', 'text' => '#2E2A2B', 'bg' => '#FBF8F5'],
            ],
            [
                'key' => 'retail',
                'name_ar' => 'متاجر ومحلات',
                'name_en' => 'Retail stores',
                'sections_blueprint' => ['pages' => [
                    self::page('home', ['hero', 'products', 'about', 'testimonials', 'faq', 'cta'], true),
                    self::page('products', ['products']),
                    $contact,
                ]],
                'stock_keywords' => ['retail store', 'shopping', 'product display', 'boutique shelves'],
                'ai_hints' => 'Friendly, direct tone. Product range, prices/offers, delivery, branches, returns.',
                'default_palette' => ['primary' => '#1D3557', 'secondary' => '#E63946', 'accent' => '#E9C46A', 'text' => '#1B1B1E', 'bg' => '#F8F7F4'],
            ],
            [
                'key' => 'real_estate',
                'name_ar' => 'تطوير عقاري',
                'name_en' => 'Real estate development',
                'sections_blueprint' => ['pages' => [
                    self::page('home', ['hero', 'about', 'projects', 'stats', 'testimonials', 'cta'], true),
                    self::page('projects', ['projects', 'gallery']),
                    $contact,
                ]],
                'stock_keywords' => ['modern residential building', 'luxury apartment interior', 'villa exterior', 'city skyline'],
                'ai_hints' => 'Aspirational yet credible tone. Locations, unit types, payment plans, delivery track record.',
                'default_palette' => ['primary' => '#23395B', 'secondary' => '#A68A64', 'accent' => '#D9C5A0', 'text' => '#1C1C1C', 'bg' => '#F8F6F2'],
            ],
        ];
    }
}
