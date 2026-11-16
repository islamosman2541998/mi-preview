<?php

namespace Database\Seeders;

use App\Enums\SiteStatus;
use App\Models\Industry;
use App\Models\Site;
use App\Services\SiteBuilder;
use Illuminate\Database\Seeder;

/**
 * Demo preview site at acme.{base}: a general-contracting company with
 * realistic ar/en content covering every section type of its blueprint.
 * Skipped when the slug already exists.
 */
class DemoSiteSeeder extends Seeder
{
    public const SLUG = 'acme';

    public function run(SiteBuilder $builder): void
    {
        if (Site::withTrashed()->where('slug', self::SLUG)->exists()) {
            $this->command?->info('Demo site already exists — skipped.');

            return;
        }

        $industry = Industry::where('key', 'contracting')->firstOrFail();

        $site = Site::create([
            'slug' => self::SLUG,
            'status' => SiteStatus::Draft,
            'company_name_ar' => 'أكمي للمقاولات العامة',
            'company_name_en' => 'Acme General Contracting',
            'industry_id' => $industry->id,
            'template' => 'industrial',
            'short_description' => 'شركة مقاولات عامة في القاهرة منذ 2009، مباني سكنية وتجارية وأعمال بنية تحتية وتشطيبات، فئة أولى.',
            'palette' => $industry->default_palette,
            'phone' => '+20 2 2614 5500',
            'whatsapp' => '+20 100 555 0142',
            'email' => 'info@acme-contracting.example',
            'address_ar' => 'التجمع الخامس، شارع التسعين الجنوبي، مبنى 12، القاهرة الجديدة',
            'address_en' => 'Bldg 12, South 90th St, Fifth Settlement, New Cairo',
            'map_url' => 'https://maps.google.com/?q=30.0131,31.4089',
            'socials' => [
                'facebook' => 'https://facebook.com/',
                'linkedin' => 'https://linkedin.com/',
            ],
            'default_locale' => 'ar',
        ]);

        $builder->build($site, content: self::content());

        $this->command?->info('Demo site created: '.$site->url());
    }

    public static function content(): array
    {
        return [
            'hero' => [
                'ar' => [
                    'eyebrow' => 'مقاولات عامة من الفئة الأولى',
                    'title' => 'نبني مشاريعك بمعايير تُسلَّم في موعدها',
                    'subtitle' => 'خمسة عشر عامًا في تنفيذ المباني السكنية والتجارية وأعمال البنية التحتية، بفريق هندسي ومعدات مملوكة للشركة.',
                    'primary_cta' => 'اطلب عرض سعر',
                    'secondary_cta' => 'شاهد مشاريعنا',
                ],
                'en' => [
                    'eyebrow' => 'Grade 1 general contractor',
                    'title' => 'We build your projects to standards, delivered on schedule',
                    'subtitle' => 'Fifteen years delivering residential, commercial and infrastructure works with an in-house engineering team and our own equipment fleet.',
                    'primary_cta' => 'Request a quote',
                    'secondary_cta' => 'See our projects',
                ],
                'shared' => ['image' => null, 'secondary_target' => '/projects'],
            ],

            'about' => [
                'ar' => [
                    'title' => 'شركة مقاولات تعرف قيمة الوقت',
                    'body' => 'تأسست أكمي عام 2009 وتعمل اليوم في القاهرة الكبرى والعاصمة الإدارية والساحل الشمالي. ننفذ المشروع من أعمال الحفر والخرسانة حتى التشطيب والتسليم، مع جدول زمني معتمد وتقارير أسبوعية للعميل.',
                    'points' => [
                        'فريق من 40 مهندسًا و350 فنيًا وعاملًا',
                        'أسطول معدات مملوك: رافعات، خلاطات، مضخات خرسانة',
                        'نظام جودة وسلامة معتمد ISO 9001 و ISO 45001',
                    ],
                ],
                'en' => [
                    'title' => 'A contractor that values your time',
                    'body' => 'Founded in 2009, Acme works across Greater Cairo, the New Administrative Capital and the North Coast. We take projects from excavation and concrete works through finishing and handover, on an approved schedule with weekly client reports.',
                    'points' => [
                        '40 engineers and 350 technicians and workers',
                        'Owned equipment fleet: cranes, mixers, concrete pumps',
                        'ISO 9001 and ISO 45001 certified quality and safety system',
                    ],
                ],
                'shared' => ['image' => null, 'since' => 2009],
            ],

            'services' => [
                'ar' => [
                    'title' => 'ما الذي ننفذه',
                    'intro' => 'نغطي دورة المشروع كاملة أو نتولى أي مرحلة منها حسب احتياجك.',
                    'items' => [
                        ['title' => 'الأعمال الإنشائية', 'description' => 'حفر، أساسات، هياكل خرسانية ومعدنية للمباني السكنية والتجارية والإدارية.'],
                        ['title' => 'البنية التحتية', 'description' => 'شبكات مياه وصرف، طرق داخلية، أعمال رصف وإنارة للمجمعات والمناطق الصناعية.'],
                        ['title' => 'التشطيبات المتكاملة', 'description' => 'تشطيب داخلي وخارجي، واجهات، أعمال كهروميكانيكية وتسليم مفتاح.'],
                        ['title' => 'الترميم والتدعيم', 'description' => 'تقييم إنشائي، تدعيم أعمدة وأسقف، معالجة الشروخ والرطوبة.'],
                        ['title' => 'إدارة المشروعات', 'description' => 'تخطيط وجدولة، إشراف على المقاولين من الباطن، ضبط التكلفة والجودة.'],
                    ],
                ],
                'en' => [
                    'title' => 'What we deliver',
                    'intro' => 'We cover the full project cycle, or take on any single stage you need.',
                    'items' => [
                        ['title' => 'Structural works', 'description' => 'Excavation, foundations, concrete and steel structures for residential, commercial and office buildings.'],
                        ['title' => 'Infrastructure', 'description' => 'Water and drainage networks, internal roads, paving and lighting for compounds and industrial zones.'],
                        ['title' => 'Full finishing', 'description' => 'Interior and exterior finishing, façades, MEP works and turnkey handover.'],
                        ['title' => 'Restoration & strengthening', 'description' => 'Structural assessment, column and slab strengthening, crack and damp treatment.'],
                        ['title' => 'Project management', 'description' => 'Planning and scheduling, subcontractor supervision, cost and quality control.'],
                    ],
                ],
                'shared' => ['items' => array_fill(0, 5, ['icon' => null, 'image' => null])],
            ],

            'stats' => [
                'ar' => ['title' => null, 'items' => [
                    ['label' => 'سنة في السوق'], ['label' => 'مشروع مُسلَّم'], ['label' => 'ألف متر مربع منفذة'], ['label' => 'يوم دون إصابات عمل'],
                ]],
                'en' => ['title' => null, 'items' => [
                    ['label' => 'Years in business'], ['label' => 'Projects delivered'], ['label' => 'Thousand m² built'], ['label' => 'Days without lost-time injury'],
                ]],
                'shared' => ['items' => [
                    ['value' => 15, 'suffix' => ''], ['value' => 120, 'suffix' => '+'], ['value' => 850, 'suffix' => ''], ['value' => 1400, 'suffix' => '+'],
                ]],
            ],

            'projects' => [
                'ar' => [
                    'title' => 'مشاريع منفذة',
                    'intro' => 'مختارات من أعمالنا خلال السنوات الأخيرة.',
                    'items' => [
                        ['title' => 'مجمع سكني النخيل', 'location' => 'القاهرة الجديدة', 'category' => 'سكني', 'description' => '14 عمارة، 336 وحدة، أعمال إنشائية وتشطيب واجهات.'],
                        ['title' => 'مبنى إداري ون بيزنس', 'location' => 'العاصمة الإدارية', 'category' => 'إداري', 'description' => 'برج إداري 12 طابقًا مع جراج من دورين.'],
                        ['title' => 'مصنع الصفا للأغذية', 'location' => 'العاشر من رمضان', 'category' => 'صناعي', 'description' => 'هنجر معدني 9,000 م² ومبنى إداري وشبكات بنية تحتية.'],
                        ['title' => 'مدرسة النيل الدولية', 'location' => 'الشيخ زايد', 'category' => 'تعليمي', 'description' => 'مبنى تعليمي ثلاثة طوابق وملاعب وتشطيب كامل.'],
                    ],
                ],
                'en' => [
                    'title' => 'Delivered projects',
                    'intro' => 'A selection of our recent work.',
                    'items' => [
                        ['title' => 'Al Nakheel Residential Compound', 'location' => 'New Cairo', 'category' => 'Residential', 'description' => '14 buildings, 336 units — structure and façade finishing.'],
                        ['title' => 'One Business Tower', 'location' => 'New Administrative Capital', 'category' => 'Office', 'description' => '12-storey office tower with a two-level car park.'],
                        ['title' => 'Al Safa Food Factory', 'location' => '10th of Ramadan City', 'category' => 'Industrial', 'description' => '9,000 m² steel hangar, admin building and infrastructure networks.'],
                        ['title' => 'Nile International School', 'location' => 'Sheikh Zayed', 'category' => 'Education', 'description' => 'Three-storey school building, sports courts and full finishing.'],
                    ],
                ],
                'shared' => ['items' => [
                    ['image' => null, 'year' => 2024], ['image' => null, 'year' => 2023],
                    ['image' => null, 'year' => 2022], ['image' => null, 'year' => 2021],
                ]],
            ],

            'certifications' => [
                'ar' => [
                    'title' => 'التصنيف والاعتمادات',
                    'intro' => 'نعمل وفق تصنيف رسمي وأنظمة إدارة معتمدة دوليًا.',
                    'items' => [
                        ['name' => 'الفئة الأولى — مباني ومرافق', 'issuer' => 'الاتحاد المصري لمقاولي التشييد والبناء'],
                        ['name' => 'ISO 9001:2015 — إدارة الجودة', 'issuer' => 'Bureau Veritas'],
                        ['name' => 'ISO 45001:2018 — السلامة والصحة المهنية', 'issuer' => 'Bureau Veritas'],
                    ],
                ],
                'en' => [
                    'title' => 'Classification & certifications',
                    'intro' => 'We operate under an official classification and internationally certified management systems.',
                    'items' => [
                        ['name' => 'Grade 1 — Buildings & utilities', 'issuer' => 'Egyptian Federation for Construction & Building Contractors'],
                        ['name' => 'ISO 9001:2015 — Quality management', 'issuer' => 'Bureau Veritas'],
                        ['name' => 'ISO 45001:2018 — Occupational health & safety', 'issuer' => 'Bureau Veritas'],
                    ],
                ],
                'shared' => ['items' => array_fill(0, 3, ['image' => null])],
            ],

            'clients' => [
                'ar' => ['title' => 'جهات عملنا معها'],
                'en' => ['title' => 'Who we have worked with'],
                'shared' => ['logos' => [
                    ['name' => 'Palm Developments', 'image' => null],
                    ['name' => 'Al Safa Foods', 'image' => null],
                    ['name' => 'Nile Education', 'image' => null],
                    ['name' => 'Capital Business Parks', 'image' => null],
                    ['name' => 'Delta Logistics', 'image' => null],
                ]],
            ],

            'testimonials' => [
                'ar' => ['title' => 'ماذا يقول عملاؤنا', 'items' => [
                    ['quote' => 'استلمنا المرحلة الأولى قبل موعدها بثلاثة أسابيع، والتقارير الأسبوعية خلّتنا متابعين كل خطوة.', 'name' => 'م. هشام عبد الرحمن', 'role' => 'مدير مشروعات — النخيل للتطوير'],
                    ['quote' => 'فريق ملتزم بالسلامة في الموقع وبالمواصفات، وأي ملاحظة كانت بتتقفل في نفس الأسبوع.', 'name' => 'أ. منى الشريف', 'role' => 'مديرة المرافق — مدارس النيل'],
                ]],
                'en' => ['title' => 'What our clients say', 'items' => [
                    ['quote' => 'We received phase one three weeks early, and the weekly reports kept us on top of every step.', 'name' => 'Eng. Hesham Abdelrahman', 'role' => 'Projects Manager — Al Nakheel Developments'],
                    ['quote' => 'A team committed to site safety and to spec — every snag was closed within the same week.', 'name' => 'Mona El Sherif', 'role' => 'Facilities Director — Nile Schools'],
                ]],
            ],

            'faq' => [
                'ar' => ['title' => 'أسئلة متكررة', 'items' => [
                    ['q' => 'هل تعملون خارج القاهرة؟', 'a' => 'نعم، ننفذ مشروعات في العاصمة الإدارية والساحل الشمالي والمدن الصناعية.'],
                    ['q' => 'كم يستغرق إعداد عرض السعر؟', 'a' => 'من 3 إلى 7 أيام عمل بعد استلام الرسومات والمواصفات.'],
                    ['q' => 'هل تقدمون ضمانًا على الأعمال؟', 'a' => 'ضمان عشري على الأعمال الإنشائية وسنة على التشطيبات وفق العقد.'],
                ]],
                'en' => ['title' => 'Frequently asked questions', 'items' => [
                    ['q' => 'Do you work outside Cairo?', 'a' => 'Yes — we deliver projects in the New Capital, the North Coast and industrial cities.'],
                    ['q' => 'How long does a quotation take?', 'a' => '3 to 7 working days after receiving drawings and specifications.'],
                    ['q' => 'Do you guarantee your work?', 'a' => 'A ten-year warranty on structural works and one year on finishing, per contract.'],
                ]],
            ],

            'gallery' => [
                'ar' => ['title' => 'من مواقع العمل'],
                'en' => ['title' => 'From our sites'],
                'shared' => ['images' => []],
            ],

            'cta' => [
                'ar' => ['title' => 'عندك مشروع جديد؟', 'body' => 'ابعت لنا الرسومات أو فكرة المشروع وهنرجع لك بتصور مبدئي وجدول زمني خلال أسبوع.', 'button' => 'كلمنا على واتساب'],
                'en' => ['title' => 'Planning a new project?', 'body' => 'Send us your drawings or the project idea and we will come back with an initial plan and schedule within a week.', 'button' => 'Message us on WhatsApp'],
            ],

            'contact' => [
                'ar' => ['title' => 'تواصل معنا', 'body' => 'مكتبنا الرئيسي في التجمع الخامس، ويسعدنا استقبالك بموعد مسبق.', 'hours' => 'السبت – الخميس، 9 صباحًا – 5 مساءً'],
                'en' => ['title' => 'Get in touch', 'body' => 'Our head office is in the Fifth Settlement — visits by appointment.', 'hours' => 'Sat – Thu, 9 am – 5 pm'],
            ],

            'map' => [
                'ar' => ['title' => 'موقع المكتب'],
                'en' => ['title' => 'Office location'],
                'shared' => ['embed_url' => 'https://www.google.com/maps?q=30.0131,31.4089&output=embed'],
            ],
        ];
    }
}
