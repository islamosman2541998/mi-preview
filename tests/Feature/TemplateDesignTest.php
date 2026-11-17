<?php

namespace Tests\Feature;

use App\Enums\NavItemType;
use App\Enums\SectionType;
use App\Models\Site;
use App\Services\SiteBuilder;
use Database\Seeders\DemoSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What every redesigned template guarantees: photos in every slot, a designed state with no photos,
 * tracked enquiry paths, and nothing that points at WhatsApp when the company has no number.
 */
class TemplateDesignTest extends TestCase
{
    use RefreshDatabase;

    public static function templates(): array
    {
        return ['industrial' => ['industrial'], 'editorial' => ['editorial'], 'boutique' => ['boutique'], 'bold' => ['bold']];
    }

    private function site(string $template, array $attributes = []): Site
    {
        $site = Site::factory()->create(array_merge([
            'slug' => 'acme', 'company_name_ar' => 'أكمي', 'company_name_en' => 'Acme', 'template' => $template,
            'whatsapp' => '+20 100 555 0142', 'phone' => '+20 2 2614 5500', 'email' => 'hello@acme.test',
            'address_ar' => 'القاهرة الجديدة', 'address_en' => 'New Cairo',
        ], $attributes));

        $content = array_replace(DemoSiteSeeder::content(), [
            'gallery' => ['ar' => ['title' => 'المعرض'], 'en' => ['title' => 'Gallery'], 'shared' => ['images' => ['https://images.example.test/g1.jpg', 'https://images.example.test/g2.jpg', 'https://images.example.test/g3.jpg']]],
            'menu' => ['ar' => ['title' => 'المنيو', 'categories' => [['name' => 'مشويات', 'items' => [['name' => 'كفتة', 'description' => 'على الفحم']]]]],
                'en' => ['title' => 'Menu', 'categories' => [['name' => 'Grills', 'items' => [['name' => 'Kofta', 'description' => 'Charcoal']]]]],
                'shared' => ['prices' => [[180]]]],
            'booking' => ['ar' => ['title' => 'احجز', 'body' => 'اختار', 'button' => 'احجز الآن', 'options' => ['كشف', 'متابعة']],
                'en' => ['title' => 'Book', 'body' => 'Pick', 'button' => 'Book now', 'options' => ['Consultation', 'Follow-up']]],
            'products' => ['ar' => ['title' => 'المنتجات', 'items' => [['name' => 'طفاية 6 كجم', 'description' => 'بودرة'], ['name' => 'خرطوم حريق', 'description' => '30 متر']]],
                'en' => ['title' => 'Products', 'items' => [['name' => '6kg extinguisher', 'description' => 'Powder'], ['name' => 'Fire hose', 'description' => '30 m']]],
                'shared' => ['items' => [['image' => null, 'price' => 950], ['image' => null, 'price' => 1200]]]],
            'map' => ['ar' => ['title' => 'موقعنا'], 'en' => ['title' => 'Find us'], 'shared' => ['embed_url' => 'https://www.google.com/maps/embed?pb=x']],
        ]);

        $site = app(SiteBuilder::class)->build($site, ['pages' => [
            ['slug' => 'home', 'title' => ['ar' => 'الرئيسية', 'en' => 'Home'], 'is_home' => true,
                'sections' => array_map(fn ($t) => $t->value, SectionType::cases())],
            ['slug' => 'about', 'title' => ['ar' => 'من نحن', 'en' => 'About'], 'sections' => ['about', 'testimonials']],
            ['slug' => 'projects', 'title' => ['ar' => 'مشاريعنا', 'en' => 'Projects'], 'sections' => ['projects']],
        ]], $content);

        $parent = $site->navItems()->create(['label_ar' => 'المزيد', 'label_en' => 'More', 'type' => NavItemType::Page, 'target' => 'about', 'sort' => 50]);
        $site->navItems()->create(['parent_id' => $parent->id, 'label_ar' => 'من نحن', 'label_en' => 'About us', 'type' => NavItemType::Page, 'target' => 'about']);

        return $site;
    }

    /** A distinct photo in every image slot of the home page; returns the URLs. */
    private function fillPhotos(Site $site): array
    {
        $url = fn (string $name) => "https://images.example.test/{$name}.jpg";
        $slots = [
            'hero' => ['image' => 'hero'], 'about' => ['image' => 'about-1', 'image2' => 'about-2'],
            'stats' => ['image' => 'stats'], 'cta' => ['image' => 'cta'], 'faq' => ['image' => 'faq'], 'booking' => ['image' => 'booking'],
        ];

        foreach ($site->pages()->where('is_home', true)->first()->sections as $section) {
            $content = $section->content;
            foreach ($slots[$section->type->value] ?? [] as $key => $name) {
                $content['shared'][$key] = $url($name);
            }
            if (in_array($section->type, [SectionType::Services, SectionType::Projects, SectionType::Products], true)) {
                $content['shared']['items'][0]['image'] = $url($section->type->value);
            }
            $section->update(['content' => $content]);
        }

        return array_map($url, ['hero', 'about-1', 'about-2', 'stats', 'cta', 'faq', 'booking', 'services', 'projects', 'products']);
    }

    private function stripPhotos(Site $site): void
    {
        foreach ($site->sections()->get() as $section) {
            $content = $section->content;
            array_walk_recursive($content, function (&$value, $key) {
                if (in_array($key, ['image', 'image2'], true)) {
                    $value = null;
                }
            });
            unset($content['shared']['images']);
            $section->update(['content' => $content]);
        }
    }

    private function main(string $html): string
    {
        $start = strpos($html, '<main');

        return substr($html, $start, strpos($html, '</main>') - $start);
    }

    #[Test]
    #[DataProvider('templates')]
    public function every_image_slot_shows_its_photo(string $template): void
    {
        $photos = $this->fillPhotos($this->site($template));

        $html = $this->get('http://acme.localhost/')->assertOk()->getContent();

        foreach ($photos as $photo) {
            $this->assertStringContainsString('src="'.$photo.'"', $html, "{$template}: {$photo} not rendered");
        }
    }

    #[Test]
    #[DataProvider('templates')]
    public function without_photos_every_section_still_renders_and_no_image_is_emitted(string $template): void
    {
        $site = $this->site($template);
        $this->stripPhotos($site);

        foreach (['/', '/en', '/about', '/projects'] as $path) {
            $html = $this->get('http://acme.localhost'.$path)->assertOk()->getContent();
            $this->assertStringNotContainsString('<img', $this->main($html), "{$template}{$path}: <img> without a photo");
            $this->assertStringNotContainsString('src=""', $html);
        }

        $html = $this->get('http://acme.localhost/')->getContent();
        foreach (SectionType::cases() as $type) {
            if ($type !== SectionType::Gallery) {
                $this->assertStringContainsString('data-section="'.$type->value.'"', $html, "{$template}: {$type->value} missing");
            }
        }
    }

    #[Test]
    #[DataProvider('templates')]
    public function enquiry_paths_are_tracked(string $template): void
    {
        $this->site($template);

        $html = $this->get('http://acme.localhost/')->assertOk()->getContent();

        foreach (['data-wa-form', 'data-wa="201005550142"', 'data-track-label="contact_form"', 'data-track-label="product_order"',
            'data-track-label="service"', 'data-track-label="hero"', 'data-track-label="cta"', 'data-count="850"', 'data-lightbox', 'data-reveal'] as $hook) {
            $this->assertStringContainsString($hook, $html, "{$template}: {$hook} missing");
        }
    }

    #[Test]
    #[DataProvider('templates')]
    public function without_whatsapp_nothing_points_at_whatsapp(string $template): void
    {
        config(['preview.brand_whatsapp' => null]);
        $this->site($template, ['whatsapp' => null]);

        $html = $this->get('http://acme.localhost/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-wa-form', $html, $template);
        $this->assertStringNotContainsString('wa.me', $html, $template);
        $this->assertStringContainsString('data-track="phone"', $html, "{$template}: falls back to calling");
    }

    #[Test]
    #[DataProvider('templates')]
    public function services_show_their_icons_and_projects_page_filters(string $template): void
    {
        $site = $this->site($template);
        $services = $site->sections()->where('type', SectionType::Services)->first();
        $content = $services->content;
        $content['shared']['items'][0]['icon'] = 'crane';
        $content['shared']['items'][1]['icon'] = 'nope';
        $services->update(['content' => $content]);

        $this->get('http://acme.localhost/')->assertOk()
            ->assertSee('icon-crane', false)
            ->assertSee('icon-sparkle', false)
            ->assertDontSee('icon-nope', false);

        $this->get('http://acme.localhost/projects')->assertOk()
            ->assertSee('data-filter-scope', false)
            ->assertSee('data-category="سكني"', false);
    }

    #[Test]
    #[DataProvider('templates')]
    public function inner_page_banner_uses_a_site_photo(string $template): void
    {
        $this->fillPhotos($this->site($template));

        $this->get('http://acme.localhost/about')->assertOk()
            ->assertSee('page-head--photo', false)
            ->assertSee('src="https://images.example.test/hero.jpg"', false);
    }
}
