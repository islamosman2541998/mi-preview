<?php

namespace Tests\Feature;

use App\Enums\NavItemType;
use App\Enums\SectionType;
use App\Models\Site;
use App\Services\SiteBuilder;
use App\Support\Palette;
use Database\Seeders\DemoSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndustrialTemplateTest extends TestCase
{
    use RefreshDatabase;

    /** One page with every section type, filled with full content. */
    private function kitchenSink(array $attributes = []): Site
    {
        $site = Site::factory()->create(array_merge([
            'slug' => 'acme',
            'company_name_ar' => 'أكمي',
            'company_name_en' => 'Acme',
            'whatsapp' => '+20 100 555 0142',
            'phone' => '+20 2 2614 5500',
            'socials' => ['facebook' => 'https://facebook.com/acme'],
        ], $attributes));

        $img = 'https://images.example.test/photo.jpg';

        $content = array_replace(DemoSiteSeeder::content(), [
            'gallery' => ['ar' => ['title' => 'المعرض'], 'en' => ['title' => 'Gallery'], 'shared' => ['images' => [$img, $img]]],
            'menu' => ['ar' => ['title' => 'المنيو', 'categories' => [['name' => 'مشويات', 'items' => [['name' => 'كفتة', 'description' => 'على الفحم']]]]],
                'en' => ['title' => 'Menu', 'categories' => [['name' => 'Grills', 'items' => [['name' => 'Kofta', 'description' => 'Charcoal']]]]],
                'shared' => ['prices' => [[180]]]],
            'booking' => ['ar' => ['title' => 'احجز', 'body' => 'اختار', 'button' => 'احجز الآن', 'options' => ['كشف', 'متابعة']],
                'en' => ['title' => 'Book', 'body' => 'Pick', 'button' => 'Book now', 'options' => ['Consultation', 'Follow-up']]],
            'products' => ['ar' => ['title' => 'المنتجات', 'items' => [['name' => 'طفاية 6 كجم', 'description' => 'بودرة']]],
                'en' => ['title' => 'Products', 'items' => [['name' => '6kg extinguisher', 'description' => 'Powder']]],
                'shared' => ['items' => [['image' => $img, 'price' => 950]]]],
        ]);
        $content['clients']['shared']['logos'][] = ['name' => 'Logo Co', 'image' => $img];

        $site = app(SiteBuilder::class)->build($site, ['pages' => [
            ['slug' => 'home', 'title' => ['ar' => 'الرئيسية', 'en' => 'Home'], 'is_home' => true,
                'sections' => array_map(fn ($t) => $t->value, SectionType::cases())],
            ['slug' => 'about', 'title' => ['ar' => 'من نحن', 'en' => 'About'], 'sections' => ['about']],
        ]], $content);

        // one dropdown level
        $parent = $site->navItems()->create(['label_ar' => 'المزيد', 'label_en' => 'More', 'type' => NavItemType::Page, 'target' => 'about', 'sort' => 50]);
        $site->navItems()->create(['parent_id' => $parent->id, 'label_ar' => 'من نحن', 'label_en' => 'About us', 'type' => NavItemType::Page, 'target' => 'about']);

        return $site;
    }

    #[Test]
    public function every_section_type_renders_in_both_languages(): void
    {
        $this->kitchenSink();

        foreach (['/' => 'rtl', '/en' => 'ltr'] as $path => $dir) {
            $html = $this->get('http://acme.localhost'.$path)->assertOk()->assertSee('dir="'.$dir.'"', false)->getContent();

            foreach (SectionType::cases() as $type) {
                $this->assertStringContainsString('data-section="'.$type->value.'"', $html, "{$type->value} missing on {$path}");
            }
        }
    }

    #[Test]
    public function chrome_is_present_preloader_demo_bar_whatsapp_and_tracking_hooks(): void
    {
        config(['preview.brand_whatsapp' => '+20 111 222 3333']);
        $this->kitchenSink();

        $this->get('http://acme.localhost/')
            ->assertOk()
            ->assertSee('data-preloader', false)
            ->assertSee('نموذج تجريبي لموقع', false)
            ->assertSee('أريد هذا الموقع')
            ->assertSee('https://wa.me/201112223333?text=', false)
            ->assertSee('data-track-label="demo_bar"', false)
            ->assertSee('class="wa-float"', false)
            ->assertSee('data-track="whatsapp"', false)
            ->assertSee('data-track="phone"', false)
            ->assertSee('fonts.googleapis.com/css2?family=Alexandria', false)
            ->assertSee('--c-primary:', false)
            ->assertSee('/js/t.js', false);
    }

    #[Test]
    public function demo_bar_cta_is_hidden_without_agency_number(): void
    {
        config(['preview.brand_whatsapp' => null]);
        $this->kitchenSink();

        $this->get('http://acme.localhost/')->assertOk()->assertSee('نموذج تجريبي')->assertDontSee('data-track-label="demo_bar"', false);
    }

    #[Test]
    public function preloader_can_be_disabled_per_site(): void
    {
        $this->kitchenSink(['preloader_enabled' => false]);

        $this->get('http://acme.localhost/')->assertOk()->assertDontSee('data-preloader', false);
    }

    #[Test]
    public function nav_is_driven_by_nav_items_with_dropdown_and_localized_links(): void
    {
        $this->kitchenSink();

        $this->get('http://acme.localhost/en')
            ->assertOk()
            ->assertSee('href="/en/about"', false)
            ->assertSee('More')
            ->assertSee('class="nav__sub"', false)
            ->assertSee('About us')
            ->assertSee('href="/" hreflang="ar"', false);
    }

    #[Test]
    public function inner_pages_get_a_compact_header_with_breadcrumb(): void
    {
        $this->kitchenSink();

        $this->get('http://acme.localhost/about')
            ->assertOk()
            ->assertSee('class="crumbs"', false)
            ->assertSee('<title>من نحن | أكمي</title>', false);
    }

    #[Test]
    public function empty_optional_sections_render_nothing(): void
    {
        $site = Site::factory()->create(['slug' => 'acme']);
        app(SiteBuilder::class)->build($site, ['pages' => [
            ['slug' => 'home', 'is_home' => true, 'sections' => ['gallery', 'clients', 'certifications', 'map']],
        ]]);

        $html = $this->get('http://acme.localhost/')->assertOk()->getContent();

        foreach (['gallery', 'clients', 'certifications', 'map'] as $type) {
            $this->assertStringNotContainsString('data-section="'.$type.'"', $html);
        }
    }

    #[Test]
    public function palette_picks_readable_text_colours(): void
    {
        $site = Site::factory()->make(['palette' => ['primary' => '#F2A900', 'secondary' => '#1E1E1E', 'accent' => '#fff', 'text' => '#111', 'bg' => '#fafafa']]);

        $vars = Palette::cssVariables($site);

        $this->assertSame('#14171A', $vars['--c-on-primary']);   // dark ink on amber
        $this->assertSame('#FFFFFF', $vars['--c-on-secondary']); // white on near-black
        $this->assertSame('#FFFFFF', $vars['--c-accent']);       // 3-digit hex normalised
        $this->assertGreaterThanOrEqual(4.5, Palette::contrast($vars['--c-primary-deep'], $vars['--c-on-primary-deep']));
    }

    #[Test]
    public function invalid_palette_values_fall_back_to_defaults(): void
    {
        $site = Site::factory()->make(['palette' => ['primary' => 'red; } body { display:none']]);

        $this->assertSame(Palette::DEFAULTS['primary'], Palette::base($site)['primary']);
    }
}
