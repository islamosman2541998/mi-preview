<?php

namespace App\Services;

use App\Enums\SectionType;
use App\Models\Site;

/**
 * Starter content for a freshly built section, so a new site renders
 * something sensible before AI content (phase 3) or manual edits.
 *
 * Content shape per type (templates rely on these keys):
 *  hero          ar/en: eyebrow, title, subtitle, primary_cta, secondary_cta   shared: image, secondary_target
 *  about         ar/en: title, body, points[]                                   shared: image, since
 *  services      ar/en: title, intro, items[{title, description}]               shared: items[{icon, image}]
 *  projects      ar/en: title, intro, items[{title, location, category, description}]  shared: items[{image, year}]
 *  gallery       ar/en: title                                                   shared: images[]
 *  stats         ar/en: title, items[{label}]                                   shared: items[{value, suffix}]
 *  testimonials  ar/en: title, items[{quote, name, role}]
 *  clients       ar/en: title                                                   shared: logos[{name, image}]
 *  faq           ar/en: title, items[{q, a}]
 *  cta           ar/en: title, body, button
 *  contact       ar/en: title, body, hours
 *  map           ar/en: title                                                   shared: embed_url
 *  menu          ar/en: title, categories[{name, items[{name, description}]}]   shared: prices[][]
 *  booking       ar/en: title, body, button, options[]
 *  products      ar/en: title, intro, items[{name, description}]                shared: items[{image, price}]
 *  certifications ar/en: title, intro, items[{name, issuer}]                    shared: items[{image}]
 *
 * Arrays under ar/en and shared are aligned by index.
 */
class SectionDefaults
{
    public static function for(SectionType $type, Site $site): array
    {
        $ar = $site->name('ar');
        $en = $site->name('en');

        return match ($type) {
            SectionType::Hero => [
                'ar' => [
                    'eyebrow' => $site->industry?->name('ar'),
                    'title' => $ar,
                    'subtitle' => 'خبرة وجودة والتزام في كل مشروع نقدمه لعملائنا.',
                    'primary_cta' => 'تواصل معنا',
                    'secondary_cta' => 'خدماتنا',
                ],
                'en' => [
                    'eyebrow' => $site->industry?->name('en'),
                    'title' => $en,
                    'subtitle' => 'Experience, quality and commitment in every project we deliver.',
                    'primary_cta' => 'Contact us',
                    'secondary_cta' => 'Our services',
                ],
                'shared' => ['image' => null, 'secondary_target' => '#services'],
            ],

            SectionType::About => [
                'ar' => [
                    'title' => 'من نحن',
                    'body' => "{$ar} شركة متخصصة تقدم خدماتها بخبرة فريق محترف والتزام كامل بالجودة والمواعيد.",
                    'points' => ['فريق متخصص', 'التزام بالمواعيد', 'جودة مضمونة'],
                ],
                'en' => [
                    'title' => 'About us',
                    'body' => "{$en} is a specialised company backed by a professional team and a full commitment to quality and deadlines.",
                    'points' => ['Specialised team', 'On-time delivery', 'Guaranteed quality'],
                ],
                'shared' => ['image' => null, 'since' => null],
            ],

            SectionType::Services => [
                'ar' => [
                    'title' => 'خدماتنا',
                    'intro' => 'حلول متكاملة تناسب احتياجات عملائنا.',
                    'items' => [
                        ['title' => 'خدمة أساسية', 'description' => 'وصف مختصر للخدمة وما تقدمه للعميل.'],
                        ['title' => 'خدمة متخصصة', 'description' => 'وصف مختصر للخدمة وما تقدمه للعميل.'],
                        ['title' => 'استشارات', 'description' => 'وصف مختصر للخدمة وما تقدمه للعميل.'],
                    ],
                ],
                'en' => [
                    'title' => 'Our services',
                    'intro' => 'Complete solutions tailored to our clients.',
                    'items' => [
                        ['title' => 'Core service', 'description' => 'A short description of the service and its value.'],
                        ['title' => 'Specialised service', 'description' => 'A short description of the service and its value.'],
                        ['title' => 'Consulting', 'description' => 'A short description of the service and its value.'],
                    ],
                ],
                'shared' => ['items' => [['icon' => null, 'image' => null], ['icon' => null, 'image' => null], ['icon' => null, 'image' => null]]],
            ],

            SectionType::Projects => [
                'ar' => [
                    'title' => 'مشاريعنا',
                    'intro' => 'نماذج من أعمالنا المنفذة.',
                    'items' => [
                        ['title' => 'مشروع ١', 'location' => '', 'category' => '', 'description' => ''],
                        ['title' => 'مشروع ٢', 'location' => '', 'category' => '', 'description' => ''],
                    ],
                ],
                'en' => [
                    'title' => 'Our projects',
                    'intro' => 'Selected work we have delivered.',
                    'items' => [
                        ['title' => 'Project 1', 'location' => '', 'category' => '', 'description' => ''],
                        ['title' => 'Project 2', 'location' => '', 'category' => '', 'description' => ''],
                    ],
                ],
                'shared' => ['items' => [['image' => null, 'year' => null], ['image' => null, 'year' => null]]],
            ],

            SectionType::Gallery => [
                'ar' => ['title' => 'معرض الصور'],
                'en' => ['title' => 'Gallery'],
                'shared' => ['images' => []],
            ],

            SectionType::Stats => [
                'ar' => ['title' => null, 'items' => [['label' => 'سنة خبرة'], ['label' => 'مشروع منفذ'], ['label' => 'عميل']]],
                'en' => ['title' => null, 'items' => [['label' => 'Years of experience'], ['label' => 'Projects delivered'], ['label' => 'Clients']]],
                'shared' => ['items' => [['value' => 10, 'suffix' => '+'], ['value' => 100, 'suffix' => '+'], ['value' => 50, 'suffix' => '+']]],
            ],

            SectionType::Testimonials => [
                'ar' => ['title' => 'آراء عملائنا', 'items' => [
                    ['quote' => 'التزام واضح بالمواعيد والتفاصيل من بداية العمل حتى التسليم.', 'name' => 'أحمد م.', 'role' => 'عميل'],
                    ['quote' => 'فهموا ما نحتاجه وقدموا اقتراحات عملية والنتيجة جاءت كما اتفقنا.', 'name' => 'محمود ع.', 'role' => 'صاحب مشروع'],
                    ['quote' => 'التواصل كان سريعًا وكل مرحلة كانت واضحة وموثقة.', 'name' => 'سارة ح.', 'role' => 'مديرة تشغيل'],
                    ['quote' => 'الجودة ثابتة والفريق تعامل مع ملاحظاتنا باهتمام.', 'name' => 'خالد س.', 'role' => 'مدير مشروع'],
                ]],
                'en' => ['title' => 'What our clients say', 'items' => [
                    ['quote' => 'The team stayed on schedule and managed every detail clearly from kickoff through handover.', 'name' => 'Ahmed M.', 'role' => 'Client'],
                    ['quote' => 'They understood the brief, offered practical options and delivered what we agreed.', 'name' => 'Mahmoud A.', 'role' => 'Business owner'],
                    ['quote' => 'Communication was quick and every stage was clear and documented.', 'name' => 'Sara H.', 'role' => 'Operations manager'],
                    ['quote' => 'Quality stayed consistent and the team handled our comments carefully.', 'name' => 'Khaled S.', 'role' => 'Project manager'],
                ]],
            ],

            SectionType::Clients => [
                'ar' => ['title' => 'عملاؤنا'],
                'en' => ['title' => 'Our clients'],
                'shared' => ['logos' => []],
            ],

            SectionType::Faq => [
                'ar' => ['title' => 'الأسئلة الشائعة', 'items' => [
                    ['q' => 'كيف أطلب الخدمة؟', 'a' => 'تواصل معنا عبر واتساب أو الهاتف وسنرد عليك في أقرب وقت.'],
                ]],
                'en' => ['title' => 'FAQ', 'items' => [
                    ['q' => 'How do I request a service?', 'a' => 'Reach us on WhatsApp or by phone and we will get back to you shortly.'],
                ]],
            ],

            SectionType::Cta => [
                'ar' => ['title' => 'جاهزين نبدأ معك', 'body' => 'تواصل معنا الآن واحصل على استشارة مجانية.', 'button' => 'تواصل عبر واتساب'],
                'en' => ['title' => 'Ready to get started?', 'body' => 'Contact us now for a free consultation.', 'button' => 'Chat on WhatsApp'],
            ],

            SectionType::Contact => [
                'ar' => ['title' => 'تواصل معنا', 'body' => 'يسعدنا تواصلك معنا في أي وقت.', 'hours' => ''],
                'en' => ['title' => 'Contact us', 'body' => 'We are happy to hear from you anytime.', 'hours' => ''],
            ],

            SectionType::Map => [
                'ar' => ['title' => 'موقعنا'],
                'en' => ['title' => 'Find us'],
                'shared' => ['embed_url' => null],
            ],

            SectionType::Menu => [
                'ar' => ['title' => 'المنيو', 'categories' => [
                    ['name' => 'الأطباق الرئيسية', 'items' => [['name' => 'طبق مميز', 'description' => '']]],
                ]],
                'en' => ['title' => 'Menu', 'categories' => [
                    ['name' => 'Main dishes', 'items' => [['name' => 'Signature dish', 'description' => '']]],
                ]],
                'shared' => ['prices' => [[null]]],
            ],

            SectionType::Booking => [
                'ar' => ['title' => 'احجز موعدك', 'body' => 'اختار الخدمة والوقت المناسب وهنتواصل معاك للتأكيد.', 'button' => 'احجز عبر واتساب', 'options' => []],
                'en' => ['title' => 'Book an appointment', 'body' => 'Pick a service and a time and we will confirm with you.', 'button' => 'Book on WhatsApp', 'options' => []],
            ],

            SectionType::Products => [
                'ar' => ['title' => 'منتجاتنا', 'intro' => '', 'items' => [['name' => 'منتج', 'description' => '']]],
                'en' => ['title' => 'Our products', 'intro' => '', 'items' => [['name' => 'Product', 'description' => '']]],
                'shared' => ['items' => [['image' => null, 'price' => null]]],
            ],

            SectionType::Certifications => [
                'ar' => ['title' => 'الشهادات والاعتمادات', 'intro' => '', 'items' => []],
                'en' => ['title' => 'Certifications', 'intro' => '', 'items' => []],
                'shared' => ['items' => []],
            ],
        };
    }
}
