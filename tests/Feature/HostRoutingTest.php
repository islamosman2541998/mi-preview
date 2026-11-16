<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Services\SiteBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HostRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function demoSite(array $attributes = []): Site
    {
        $site = Site::factory()->create(array_merge([
            'slug' => 'acme',
            'company_name_ar' => 'أكمي للمقاولات',
            'company_name_en' => 'Acme Contracting',
        ], $attributes));

        return app(SiteBuilder::class)->build($site);
    }

    #[Test]
    public function admin_host_serves_the_filament_login(): void
    {
        $this->get('http://app.localhost/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    #[Test]
    public function admin_host_root_redirects_guests_to_login(): void
    {
        $this->get('http://app.localhost/')->assertRedirect('http://app.localhost/login');
    }

    #[Test]
    public function preview_host_renders_the_site_home_page_in_arabic(): void
    {
        $this->demoSite();

        $this->get('http://acme.localhost/')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('أكمي للمقاولات')
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    #[Test]
    public function en_prefix_switches_locale_and_direction(): void
    {
        $this->demoSite();

        $this->get('http://acme.localhost/en')
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertSee('Acme Contracting');

        $this->get('http://acme.localhost/en/contact')
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertSee('page-head__title">Contact</h1>', false);
    }

    #[Test]
    public function inner_pages_render_by_slug(): void
    {
        $this->demoSite();

        $this->get('http://acme.localhost/contact')
            ->assertOk()
            ->assertSee('page-head__title">تواصل معنا</h1>', false);
    }

    #[Test]
    public function english_falls_back_to_arabic_when_translation_is_missing(): void
    {
        $this->demoSite(['company_name_en' => null]);

        $this->get('http://acme.localhost/en')->assertOk()->assertSee('أكمي للمقاولات');
    }

    #[Test]
    public function missing_and_unpublished_pages_are_404(): void
    {
        $site = $this->demoSite();
        Page::factory()->for($site)->unpublished()->create(['slug' => 'hidden']);

        $this->get('http://acme.localhost/nope')->assertNotFound();
        $this->get('http://acme.localhost/hidden')->assertNotFound();
        $this->get('http://acme.localhost/en/hidden')->assertNotFound();
    }

    #[Test]
    public function hidden_sections_are_not_rendered(): void
    {
        $site = $this->demoSite();
        Section::factory()->hidden()->create([
            'page_id' => $site->homePage->id,
            'content' => ['ar' => ['title' => 'قسم مخفي'], 'en' => ['title' => 'Hidden section']],
        ]);

        $this->get('http://acme.localhost/')->assertOk()->assertDontSee('قسم مخفي');
    }

    #[Test]
    public function unknown_slug_gets_neutral_404_with_noindex(): void
    {
        $this->get('http://ghost.localhost/')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    #[Test]
    public function archived_and_soft_deleted_sites_are_404(): void
    {
        $this->demoSite(['status' => SiteStatus::Archived]);
        $deleted = Site::factory()->create(['slug' => 'gone']);
        $deleted->delete();

        $this->get('http://acme.localhost/')->assertNotFound();
        $this->get('http://gone.localhost/')->assertNotFound();
    }

    #[Test]
    public function every_non_archived_status_is_served(): void
    {
        $site = $this->demoSite();

        foreach ([SiteStatus::Draft, SiteStatus::Sent, SiteStatus::Viewed, SiteStatus::Won, SiteStatus::Lost] as $status) {
            $site->update(['status' => $status]);
            $this->get('http://acme.localhost/')->assertOk();
        }
    }

    #[Test]
    #[DataProvider('reservedHosts')]
    public function reserved_subdomains_never_resolve_to_a_site(string $slug): void
    {
        // even if such a row slipped into the DB, the host must not serve it
        $this->demoSite(['slug' => $slug]);

        $this->get("http://{$slug}.localhost/")->assertNotFound();
    }

    public static function reservedHosts(): array
    {
        return [['www'], ['demo'], ['clinic'], ['rk-fire'], ['cpanel']];
    }

    #[Test]
    public function bare_base_domain_and_foreign_hosts_are_404(): void
    {
        $this->demoSite();

        $this->get('http://localhost/')->assertNotFound()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('http://acme.example.com/')->assertNotFound();
        $this->get('http://deep.acme.localhost/')->assertNotFound();
    }

    #[Test]
    public function host_matching_is_case_insensitive(): void
    {
        $this->demoSite();

        $this->get('http://ACME.localhost/')->assertOk();
    }
}
