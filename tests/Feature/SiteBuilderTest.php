<?php

namespace Tests\Feature;

use App\Enums\Locale;
use App\Enums\NavItemType;
use App\Enums\SectionType;
use App\Models\Industry;
use App\Models\Site;
use App\Services\SiteBuilder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSiteSeeder;
use Database\Seeders\IndustrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteBuilderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function builds_pages_and_sections_from_the_industry_blueprint(): void
    {
        $this->seed(IndustrySeeder::class);
        $industry = Industry::where('key', 'restaurant')->first();
        $site = Site::factory()->create(['industry_id' => $industry->id]);

        app(SiteBuilder::class)->build($site);

        $this->assertSame(['home', 'menu', 'contact'], $site->pages->pluck('slug')->all());
        $this->assertTrue($site->homePage->is($site->pages->first()));
        $this->assertSame(
            ['hero', 'about', 'menu', 'gallery', 'testimonials', 'cta'],
            $site->homePage->sections->map(fn ($s) => $s->type->value)->all(),
        );
        $this->assertNotEmpty($site->homePage->sections->first()->contentFor('ar')['title']);
    }

    #[Test]
    public function falls_back_to_the_default_blueprint_without_industry(): void
    {
        $site = Site::factory()->create();

        app(SiteBuilder::class)->build($site);

        $this->assertSame(['home', 'contact'], $site->pages->pluck('slug')->all());
    }

    #[Test]
    public function generates_nav_with_page_links_and_whatsapp_cta(): void
    {
        $site = Site::factory()->create(['whatsapp' => '+20 100 555 0142']);

        app(SiteBuilder::class)->build($site);
        $nav = $site->navItems;

        $this->assertSame(['home', 'contact', null], $nav->pluck('target')->all());
        $cta = $nav->last();
        $this->assertTrue($cta->is_cta);
        $this->assertSame(NavItemType::Whatsapp, $cta->type);
        $this->assertSame('https://wa.me/201005550142', $cta->href($site));
        $this->assertSame('whatsapp', $cta->trackAs());

        // links follow the locale URL scheme; home links to "/"
        $this->assertSame('/', $nav[0]->href($site, Locale::Ar, 'home'));
        $this->assertSame('/en', $nav[0]->href($site, Locale::En, 'home'));
        $this->assertSame('/en/contact', $nav[1]->href($site, Locale::En, 'home'));
    }

    #[Test]
    public function single_page_sites_get_anchor_links(): void
    {
        $site = Site::factory()->create(['whatsapp' => null, 'phone' => '+20 2 1234 5678']);

        app(SiteBuilder::class)->build($site, ['pages' => [
            ['slug' => 'home', 'title' => ['ar' => 'الرئيسية', 'en' => 'Home'], 'is_home' => true,
                'sections' => ['hero', 'about', 'services', 'stats', 'contact']],
        ]]);

        $nav = $site->navItems;
        $this->assertSame(
            ['home', 'about', 'services', 'contact', null],
            $nav->pluck('target')->all(),
        );
        $this->assertSame('/#services', $nav[2]->href($site, Locale::Ar));
        $this->assertSame(NavItemType::Phone, $nav->last()->type);
        $this->assertSame('tel:+20212345678', $nav->last()->href($site));
    }

    #[Test]
    public function content_overrides_win_over_defaults(): void
    {
        $site = Site::factory()->create();

        app(SiteBuilder::class)->build($site, content: [
            'hero' => ['ar' => ['title' => 'عنوان مخصص'], 'en' => ['title' => 'Custom title'], 'shared' => ['image' => 'x.jpg']],
            'contact.contact' => ['ar' => ['title' => 'كلمنا']],
        ]);

        $hero = $site->homePage->sections->first();
        $this->assertSame(SectionType::Hero, $hero->type);
        $this->assertSame(['image' => 'x.jpg', 'title' => 'Custom title'], $hero->contentFor('en'));

        $contact = $site->pages->firstWhere('slug', 'contact')->sections->first();
        $this->assertSame('كلمنا', $contact->contentFor('en')['title']); // en missing → ar
    }

    #[Test]
    public function refuses_to_build_twice(): void
    {
        $site = Site::factory()->create();
        app(SiteBuilder::class)->build($site);

        $this->expectException(LogicException::class);
        app(SiteBuilder::class)->build($site);
    }

    #[Test]
    public function every_industry_blueprint_builds_with_valid_section_types(): void
    {
        $this->seed(IndustrySeeder::class);

        foreach (Industry::all() as $industry) {
            $site = Site::factory()->create(['industry_id' => $industry->id]);
            app(SiteBuilder::class)->build($site);

            $this->assertSame(1, $site->pages->where('is_home', true)->count(), $industry->key);
            $this->assertGreaterThan(0, $site->sections()->count(), $industry->key);
        }
    }

    #[Test]
    public function database_seeder_creates_admin_industries_and_demo_site(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@example.test']);
        $this->assertSame(8, Industry::count());

        $demo = Site::where('slug', DemoSiteSeeder::SLUG)->firstOrFail();
        $this->assertSame(['home', 'about', 'services', 'projects', 'contact'], $demo->pages->pluck('slug')->all());
        $this->assertSame('Acme General Contracting', $demo->name('en'));

        // re-running is safe (idempotent)
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(1, Site::where('slug', DemoSiteSeeder::SLUG)->count());

        $this->get('http://acme.localhost/')->assertOk()->assertSee('نبني مشاريعك');
        $this->get('http://acme.localhost/en/projects')->assertOk()->assertSee('Delivered projects');
    }
}
