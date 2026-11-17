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
    public function chrome_is_present_preloader_whatsapp_and_tracking_hooks(): void
    {
        config(['preview.brand_whatsapp' => '+20 111 222 3333']);
        $this->kitchenSink();

        $this->get('http://acme.localhost/')
            ->assertOk()
            ->assertSee('data-preloader', false)
            ->assertDontSee('نموذج تجريبي لموقع', false)
            ->assertDontSee('data-track-label="demo_bar"', false)
            ->assertSee('class="wa-float"', false)
            ->assertSee('data-track="whatsapp"', false)
            ->assertSee('data-track="phone"', false)
            ->assertSee('fonts.googleapis.com/css2?family=Cairo', false)
            ->assertSee('family=Tajawal', false)
            ->assertSee('--c-primary:', false)
            ->assertSee('/js/t.js', false);
    }

    #[Test]
    public function previews_carry_no_agency_demo_bar(): void
    {
        config(['preview.brand_whatsapp' => '+20 111 222 3333']);
        $this->kitchenSink();

        $this->get('http://acme.localhost/')->assertOk()
            ->assertDontSee('class="demo-bar"', false)
            ->assertDontSee('https://wa.me/201112223333', false);
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

    /** Put a distinct photo in every image slot of the kitchen-sink home page. */
    private function fillPhotos(Site $site): array
    {
        $url = fn (string $name) => "https://images.example.test/{$name}.jpg";
        $set = [
            'hero' => ['image' => $url('hero')],
            'about' => ['image' => $url('about-1'), 'image2' => $url('about-2')],
            'stats' => ['image' => $url('stats')],
            'cta' => ['image' => $url('cta')],
            'faq' => ['image' => $url('faq')],
            'booking' => ['image' => $url('booking')],
        ];

        foreach ($site->sections()->get() as $section) {
            $content = $section->content;
            foreach ($set[$section->type->value] ?? [] as $key => $value) {
                $content['shared'][$key] = $value;
            }
            if (in_array($section->type, [SectionType::Services, SectionType::Projects], true)) {
                $content['shared']['items'][0]['image'] = $url($section->type->value);
            }
            $section->update(['content' => $content]);
        }

        return array_map($url, ['hero', 'about-1', 'about-2', 'stats', 'cta', 'faq', 'booking', 'services', 'projects']);
    }

    /** Remove every photo from the site's sections. */
    private function stripPhotos(Site $site): void
    {
        foreach ($site->sections()->get() as $section) {
            $content = $section->content;
            array_walk_recursive($content, function (&$value, $key) {
                if ($key === 'image' || $key === 'image2') {
                    $value = null;
                }
            });
            unset($content['shared']['images']);
            $section->update(['content' => $content]);
        }
    }

    #[Test]
    public function industrial_uses_photos_in_every_slot(): void
    {
        $photos = $this->fillPhotos($this->kitchenSink());

        $html = $this->get('http://acme.localhost/')->assertOk()->getContent();

        foreach ($photos as $photo) {
            $this->assertStringContainsString('src="'.$photo.'"', $html, "{$photo} is not rendered");
        }
    }

    #[Test]
    public function industrial_renders_without_any_photos(): void
    {
        $site = $this->kitchenSink();
        $this->stripPhotos($site);

        $html = $this->get('http://acme.localhost/')->assertOk()->getContent();
        $main = substr($html, strpos($html, '<main'), strpos($html, '</main>') - strpos($html, '<main'));

        foreach (SectionType::cases() as $type) {
            if ($type !== SectionType::Gallery) { // a gallery with no photos renders nothing
                $this->assertStringContainsString('data-section="'.$type->value.'"', $html, "{$type->value} missing");
            }
        }
        $this->assertStringNotContainsString('<img', $main, 'no image elements when the site has no photos');
        $this->assertStringNotContainsString('src=""', $html);
    }

    #[Test]
    public function inner_page_header_uses_the_home_hero_photo_then_a_page_photo(): void
    {
        $site = $this->kitchenSink();
        $this->fillPhotos($site);

        $this->get('http://acme.localhost/about')
            ->assertOk()
            ->assertSee('page-head--photo', false)
            ->assertSee('class="page-head__bg" src="https://images.example.test/hero.jpg"', false);

        // no hero photo → the first photo of the page itself
        $this->stripPhotos($site);
        $about = $site->pages()->where('slug', 'about')->first()->sections()->first();
        $about->update(['content' => array_replace_recursive($about->content, ['shared' => ['image' => 'https://images.example.test/own.jpg']])]);

        $this->get('http://acme.localhost/about')
            ->assertOk()
            ->assertSee('class="page-head__bg" src="https://images.example.test/own.jpg"', false);

        // no photos at all → plain band
        $this->stripPhotos($site);
        $this->get('http://acme.localhost/about')->assertOk()->assertDontSee('page-head--photo', false)->assertSee('class="crumbs"', false);
    }

    #[Test]
    public function contact_form_service_links_and_product_orders_are_tracked(): void
    {
        $this->kitchenSink();

        $this->get('http://acme.localhost/')
            ->assertOk()
            ->assertSee('data-wa-form', false)
            ->assertSee('data-wa="201005550142"', false)
            ->assertSee('data-track-label="contact_form"', false)
            ->assertSee('data-track-label="product_order"', false)
            ->assertSee('data-track-label="service"', false)
            ->assertSee('data-count="850"', false)
            ->assertSee('data-lightbox', false);
    }

    #[Test]
    public function without_whatsapp_there_is_no_form_and_no_dead_whatsapp_link(): void
    {
        config(['preview.brand_whatsapp' => null]);
        $this->kitchenSink(['whatsapp' => null]);

        $html = $this->get('http://acme.localhost/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-wa-form', $html);
        $this->assertStringNotContainsString('wa.me', $html);
        $this->assertStringNotContainsString('product_order', $html);
        $this->assertStringContainsString('data-track="phone"', $html, 'falls back to calling');
    }

    #[Test]
    public function service_icons_come_from_the_content_with_a_default(): void
    {
        $site = $this->kitchenSink();
        $services = $site->sections()->where('type', SectionType::Services)->first();
        $content = $services->content;
        $content['shared']['items'][0]['icon'] = 'crane';
        $content['shared']['items'][1]['icon'] = 'not-an-icon';
        $services->update(['content' => $content]);

        $this->get('http://acme.localhost/')
            ->assertOk()
            ->assertSee('icon-crane', false)
            ->assertSee('icon-sparkle', false)
            ->assertDontSee('icon-not-an-icon', false);
    }

    #[Test]
    public function projects_page_offers_a_category_filter(): void
    {
        $site = Site::factory()->create(['slug' => 'acme']);
        app(SiteBuilder::class)->build($site, ['pages' => [
            ['slug' => 'home', 'is_home' => true, 'sections' => ['hero']],
            ['slug' => 'projects', 'title' => ['ar' => 'مشاريعنا', 'en' => 'Projects'], 'sections' => ['projects']],
        ]], DemoSiteSeeder::content());

        $this->get('http://acme.localhost/projects')
            ->assertOk()
            ->assertSee('data-filter-scope', false)
            ->assertSee('data-filter="*"', false)
            ->assertSee('data-category="سكني"', false);
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
