<?php

namespace Tests\Feature;

use App\Enums\NavItemType;
use App\Enums\SectionType;
use App\Models\Site;
use App\Services\SiteBuilder;
use App\Support\Fonts;
use Database\Seeders\DemoSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Every template renders every section type, the shared chrome and the tracking hooks. */
class TemplatesTest extends TestCase
{
    use RefreshDatabase;

    /** Kept in step with config('preview.templates') by every_template_has_a_layout_and_all_section_views. */
    private const TEMPLATES = ['industrial', 'editorial', 'boutique', 'bold'];

    public static function templates(): array
    {
        return array_combine(self::TEMPLATES, array_map(fn ($t) => [$t], self::TEMPLATES));
    }

    /** One page with every section type, full content, a dropdown in the nav. */
    private function kitchenSink(string $template, array $attributes = []): Site
    {
        $site = Site::factory()->create(array_merge([
            'slug' => 'acme',
            'company_name_ar' => 'أكمي',
            'company_name_en' => 'Acme',
            'template' => $template,
            'whatsapp' => '+20 100 555 0142',
            'phone' => '+20 2 2614 5500',
            'email' => 'hello@acme.test',
            'address_ar' => 'القاهرة الجديدة',
            'address_en' => 'New Cairo',
            'socials' => ['instagram' => 'https://instagram.com/acme'],
        ], $attributes));

        $img = 'https://images.example.test/photo.jpg';

        $content = array_replace(DemoSiteSeeder::content(), [
            'hero' => array_replace_recursive(DemoSiteSeeder::content()['hero'], ['shared' => ['image' => $img]]),
            'gallery' => ['ar' => ['title' => 'المعرض'], 'en' => ['title' => 'Gallery'], 'shared' => ['images' => [$img, $img, $img]]],
            'menu' => ['ar' => ['title' => 'المنيو', 'categories' => [['name' => 'مشويات', 'items' => [['name' => 'كفتة', 'description' => 'على الفحم']]]]],
                'en' => ['title' => 'Menu', 'categories' => [['name' => 'Grills', 'items' => [['name' => 'Kofta', 'description' => 'Charcoal']]]]],
                'shared' => ['prices' => [[180]]]],
            'booking' => ['ar' => ['title' => 'احجز', 'body' => 'اختار', 'button' => 'احجز الآن', 'options' => ['كشف', 'متابعة']],
                'en' => ['title' => 'Book', 'body' => 'Pick', 'button' => 'Book now', 'options' => ['Consultation', 'Follow-up']]],
            'products' => ['ar' => ['title' => 'المنتجات', 'items' => [['name' => 'طفاية 6 كجم', 'description' => 'بودرة']]],
                'en' => ['title' => 'Products', 'items' => [['name' => '6kg extinguisher', 'description' => 'Powder']]],
                'shared' => ['items' => [['image' => $img, 'price' => 950]]]],
            'map' => ['ar' => ['title' => 'موقعنا'], 'en' => ['title' => 'Find us'], 'shared' => ['embed_url' => 'https://www.google.com/maps/embed?pb=x']],
        ]);

        $site = app(SiteBuilder::class)->build($site, ['pages' => [
            ['slug' => 'home', 'title' => ['ar' => 'الرئيسية', 'en' => 'Home'], 'is_home' => true,
                'sections' => array_map(fn ($t) => $t->value, SectionType::cases())],
            ['slug' => 'about', 'title' => ['ar' => 'من نحن', 'en' => 'About'], 'sections' => ['about']],
        ]], $content);

        $parent = $site->navItems()->create(['label_ar' => 'المزيد', 'label_en' => 'More', 'type' => NavItemType::Page, 'target' => 'about', 'sort' => 50]);
        $site->navItems()->create(['parent_id' => $parent->id, 'label_ar' => 'من نحن', 'label_en' => 'About us', 'type' => NavItemType::Page, 'target' => 'about']);

        return $site;
    }

    #[Test]
    #[DataProvider('templates')]
    public function every_section_type_renders_in_both_languages(string $template): void
    {
        $this->kitchenSink($template);

        foreach (['/' => ['rtl', 'كفتة'], '/en' => ['ltr', 'Kofta']] as $path => [$dir, $dish]) {
            $html = $this->get('http://acme.localhost'.$path)
                ->assertOk()
                ->assertSee('dir="'.$dir.'"', false)
                ->assertSee('class="tpl-'.$template.'"', false)
                ->assertSee($dish)
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
                ->getContent();

            foreach (SectionType::cases() as $type) {
                $this->assertStringContainsString('data-section="'.$type->value.'"', $html, "{$template}: {$type->value} missing on {$path}");
            }

            $this->assertDoesNotMatchRegularExpression('/preview\.(?!js)[a-z_]+/', strip_tags($html), "{$template}: untranslated key on {$path}");
        }
    }

    #[Test]
    #[DataProvider('templates')]
    public function chrome_tracking_hooks_and_fonts_are_present(string $template): void
    {
        config(['preview.brand_whatsapp' => '+20 111 222 3333']);
        $site = $this->kitchenSink($template);
        $font = Fonts::pairing($site)['heading']['family'];

        $this->get('http://acme.localhost/')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertSee('data-preloader', false)
            ->assertDontSee('data-track-label="demo_bar"', false)
            ->assertSee('class="wa-float"', false)
            ->assertSee('data-track="whatsapp"', false)
            ->assertSee('data-track="phone"', false)
            ->assertSee('data-masthead', false)
            ->assertSee('data-nav-toggle', false)
            ->assertSee('data-sub-toggle', false)
            ->assertSee('fonts.googleapis.com/css2?family='.str_replace(' ', '+', $font), false)
            ->assertSee('--c-primary:', false)
            ->assertSee('/js/t.js', false)
            ->assertSee('href="/en"', false)
            ->assertSee('booking_option', false);
    }

    #[Test]
    public function industrial_template_uses_the_transparent_logo_and_sliders(): void
    {
        $this->kitchenSink('industrial', [
            'logo_path' => 'https://images.example.test/logo-full.jpg',
            'logo_nobg_path' => 'https://images.example.test/logo-transparent.png',
        ]);

        $html = $this->get('http://acme.localhost/')
            ->assertOk()
            ->assertSee('https://images.example.test/logo-transparent.png', false)
            ->assertDontSee('https://images.example.test/logo-full.jpg', false)
            ->assertSee('data-carousel', false)
            ->assertSee('data-carousel-track', false)
            ->assertSee('data-carousel-prev', false)
            ->assertSee('data-carousel-next', false)
            ->getContent();

        $this->assertGreaterThanOrEqual(2, substr_count($html, 'data-carousel'));
    }

    #[Test]
    #[DataProvider('templates')]
    public function inner_pages_get_a_title_and_breadcrumb(string $template): void
    {
        $this->kitchenSink($template);

        $this->get('http://acme.localhost/about')->assertOk()
            ->assertSee('class="page-head', false)
            ->assertSee('<h1 class="page-head__title">من نحن</h1>', false);
    }

    #[Test]
    #[DataProvider('templates')]
    public function sections_without_content_render_nothing(string $template): void
    {
        $site = Site::factory()->create(['slug' => 'acme', 'template' => $template]);
        app(SiteBuilder::class)->build($site, ['pages' => [
            ['slug' => 'home', 'title' => ['ar' => 'الرئيسية', 'en' => 'Home'], 'is_home' => true,
                'sections' => ['gallery', 'projects', 'clients', 'faq', 'testimonials', 'stats', 'menu', 'products', 'certifications', 'map']],
        ]], array_fill_keys(['gallery', 'projects', 'clients', 'faq', 'testimonials', 'stats', 'menu', 'products', 'certifications', 'map'], ['ar' => ['title' => 'x'], 'en' => ['title' => 'x']]));

        $html = $this->get('http://acme.localhost/')->assertOk()->getContent();

        foreach (['gallery', 'projects', 'clients', 'faq', 'testimonials', 'stats', 'menu', 'products', 'certifications', 'map'] as $type) {
            $this->assertStringNotContainsString('data-section="'.$type.'"', $html, "{$template}: empty {$type} should not render");
        }
    }

    #[Test]
    #[DataProvider('templates')]
    public function pages_render_without_photos_or_contact_details(string $template): void
    {
        $site = Site::factory()->create(['slug' => 'acme', 'template' => $template, 'whatsapp' => null, 'phone' => null, 'email' => null, 'logo_path' => null, 'logo_nobg_path' => null]);
        app(SiteBuilder::class)->build($site, ['pages' => [
            ['slug' => 'home', 'title' => ['ar' => 'الرئيسية', 'en' => 'Home'], 'is_home' => true,
                'sections' => ['hero', 'about', 'services', 'projects', 'contact', 'cta', 'booking']],
        ]], DemoSiteSeeder::content());

        $this->get('http://acme.localhost/')->assertOk()->assertDontSee('data-track="whatsapp"', false);
        $this->get('http://acme.localhost/en')->assertOk();
    }

    #[Test]
    public function every_template_has_a_layout_and_all_section_views(): void
    {
        $this->assertSame(self::TEMPLATES, array_keys(config('preview.templates')), 'update TEMPLATES when adding a template');

        foreach (array_keys(config('preview.templates')) as $template) {
            $this->assertTrue(view()->exists("templates.{$template}.layout"), "{$template} layout");
            foreach (SectionType::cases() as $type) {
                $this->assertTrue(view()->exists("templates.{$template}.sections.{$type->value}"), "{$template}: {$type->value}");
            }
            $this->assertArrayHasKey(config("preview.templates.{$template}.font"), config('preview.fonts'), "{$template}: font pairing");
        }
    }

    #[Test]
    public function serif_pairings_fall_back_to_a_serif_stack(): void
    {
        $site = Site::factory()->create(['template' => 'editorial']);

        $this->assertStringContainsString('--f-heading:"Amiri", "Times New Roman", serif', Fonts::cssVariables($site));
        $this->assertStringContainsString('--f-body:"IBM Plex Sans Arabic", system-ui', Fonts::cssVariables($site));
    }

    #[Test]
    public function industrial_font_hierarchy_can_be_swapped_between_cairo_and_tajawal(): void
    {
        $cairoFirst = Site::factory()->make(['template' => 'industrial']);
        $tajawalFirst = Site::factory()->make(['template' => 'industrial', 'font_key' => 'tajawal-cairo']);

        $this->assertSame('Cairo', Fonts::pairing($cairoFirst)['heading']['family']);
        $this->assertSame('Tajawal', Fonts::pairing($cairoFirst)['body']['family']);
        $this->assertSame('Tajawal', Fonts::pairing($tajawalFirst)['heading']['family']);
        $this->assertSame('Cairo', Fonts::pairing($tajawalFirst)['body']['family']);
    }
}
