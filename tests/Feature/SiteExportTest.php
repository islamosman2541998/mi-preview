<?php

namespace Tests\Feature;

use App\Enums\NavItemType;
use App\Enums\SectionType;
use App\Filament\Resources\Sites\Pages\EditSite;
use App\Filament\Resources\Sites\Pages\ListSites;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use App\Services\SiteExporter;
use Database\Seeders\IndustrySeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class SiteExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->seed(IndustrySeeder::class);
        Http::preventStrayRequests();
    }

    private function site(): Site
    {
        $site = Site::factory()->create([
            'slug' => 'acme', 'company_name_ar' => 'أكمي', 'company_name_en' => 'Acme',
            'logo_path' => 'sites/logos/logo.png', 'logo_nobg_path' => 'sites/logos/logo-nobg.png',
            'palette' => ['primary' => '#1F3A5F', 'secondary' => '#F2A900'],
            'seo' => ['title_ar' => 'أكمي للمقاولات', 'description_en' => 'Contractor in Cairo'],
            'socials' => ['facebook' => 'https://facebook.com/acme', 'x' => null],
        ]);
        foreach (['sites/logos/logo.png' => 'LOGO', 'sites/logos/logo-nobg.png' => 'NOBG', "sites/{$site->id}/media/hero.jpg" => 'HERO', "sites/{$site->id}/sections/a/photo.jpg" => 'P1', "sites/{$site->id}/sections/b/photo.jpg" => 'P2'] as $path => $bytes) {
            Storage::disk('public')->put($path, $bytes);
        }

        $home = Page::factory()->for($site)->create(['slug' => 'home', 'is_home' => true, 'title_ar' => 'الرئيسية', 'title_en' => 'Home', 'sort' => 0]);
        Page::factory()->for($site)->create(['slug' => 'about', 'title_ar' => 'من نحن', 'title_en' => 'About', 'sort' => 1, 'is_published' => false]);

        Section::factory()->for($home)->create(['type' => SectionType::Hero, 'sort' => 0, 'content' => [
            'ar' => ['title' => 'نبني'], 'en' => ['title' => 'We build'],
            'shared' => ['image' => "sites/{$site->id}/media/hero.jpg", 'secondary_target' => '/projects'],
            'ai' => ['image_query' => 'construction', 'generated_at' => 'x'],
        ]]);
        Section::factory()->for($home)->create(['type' => SectionType::Projects, 'sort' => 1, 'variant' => 'grid', 'content' => [
            'ar' => ['items' => [['title' => 'أ'], ['title' => 'ب'], ['title' => 'ج']]],
            'en' => ['items' => [['title' => 'A'], ['title' => 'B'], ['title' => 'C']]],
            'shared' => ['items' => [
                ['image' => "sites/{$site->id}/sections/a/photo.jpg"],
                ['image' => "sites/{$site->id}/sections/b/photo.jpg"],
                ['image' => "sites/{$site->id}/sections/gone.jpg"], // file no longer exists
            ]],
        ]]);
        Section::factory()->for($home)->create(['type' => SectionType::Map, 'sort' => 2, 'content' => [
            'ar' => ['title' => 'موقعنا'], 'shared' => ['embed_url' => 'https://www.google.com/maps/embed?pb=1'],
        ]]);

        $site->media()->first()?->update(['credit' => 'Image by u from Pixabay', 'source' => 'pixabay']);
        $parent = $site->navItems()->create(['label_ar' => 'المزيد', 'label_en' => 'More', 'type' => NavItemType::Page, 'target' => 'about', 'sort' => 1]);
        $site->navItems()->create(['parent_id' => $parent->id, 'label_ar' => 'من نحن', 'label_en' => 'About', 'type' => NavItemType::Page, 'target' => 'about']);
        $site->navItems()->create(['label_ar' => 'واتساب', 'label_en' => 'WhatsApp', 'type' => NavItemType::Whatsapp, 'is_cta' => true, 'sort' => 2]);

        return $site->fresh();
    }

    /** @return array{0: array, 1: ZipArchive} */
    private function open(string $path): array
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'export is a valid zip');

        return [json_decode($zip->getFromName('site.json'), true), $zip];
    }

    #[Test]
    public function export_contains_the_site_data_media_and_a_readme(): void
    {
        $path = app(SiteExporter::class)->export($this->site());
        [$data, $zip] = $this->open($path);

        $this->assertSame('mi-preview-export', $data['format']);
        $this->assertSame(1, $data['version']);
        $this->assertNotFalse($zip->getFromName('README.md'));

        $site = $data['site'];
        $this->assertSame(['ar' => 'أكمي', 'en' => 'Acme'], $site['name']);
        $this->assertSame('#1F3A5F', $site['palette']['primary']);
        $this->assertCount(10, $site['palette']['shades']['primary']);
        $this->assertArrayHasKey('--c-on-primary', $site['css_variables']);
        $this->assertStringContainsString('fonts.googleapis.com', $site['fonts']['google_fonts_url']);
        $this->assertSame(['facebook' => 'https://facebook.com/acme'], $site['socials']);
        $this->assertSame('أكمي للمقاولات', $site['seo']['title']['ar']);
        $this->assertSame('Contractor in Cairo', $site['seo']['description']['en']);

        // logos shipped and referenced by their zip name
        $this->assertSame('LOGO', $zip->getFromName($site['logo']));
        $this->assertSame('NOBG', $zip->getFromName($site['logo_transparent']));
    }

    #[Test]
    public function pages_sections_and_navigation_keep_their_structure(): void
    {
        [$data] = $this->open(app(SiteExporter::class)->export($this->site()));

        $this->assertSame(['home', 'about'], array_column($data['pages'], 'slug'));
        $this->assertFalse($data['pages'][1]['is_published']);
        $this->assertSame(['hero', 'projects', 'map'], array_column($data['pages'][0]['sections'], 'type'));
        $this->assertSame('grid', $data['pages'][0]['sections'][1]['variant']);
        $this->assertSame('We build', $data['pages'][0]['sections'][0]['content']['en']['title']);
        $this->assertArrayNotHasKey('ai', $data['pages'][0]['sections'][0]['content'], 'internal AI bookkeeping stripped');

        $nav = $data['navigation'];
        $this->assertCount(2, $nav);
        $this->assertSame('About', $nav[0]['children'][0]['label']['en']);
        $this->assertTrue($nav[1]['is_cta']);
        $this->assertSame('whatsapp', $nav[1]['type']);
    }

    #[Test]
    public function image_paths_are_rewritten_to_bundled_files_and_links_are_left_alone(): void
    {
        $site = $this->site();
        [$data, $zip] = $this->open(app(SiteExporter::class)->export($site));
        $sections = $data['pages'][0]['sections'];

        $hero = $sections[0]['content']['shared'];
        $this->assertSame('media/hero.jpg', $hero['image']);
        $this->assertSame('HERO', $zip->getFromName('media/hero.jpg'));
        $this->assertSame('/projects', $hero['secondary_target']);

        // two different files with the same name don't overwrite each other
        $items = $sections[1]['content']['shared']['items'];
        $this->assertSame(['media/photo.jpg', 'media/photo-2.jpg'], [$items[0]['image'], $items[1]['image']]);
        $this->assertSame('P2', $zip->getFromName('media/photo-2.jpg'));

        // a missing file is reported, the map embed is not mistaken for an image
        $this->assertSame("sites/{$site->id}/sections/gone.jpg", $items[2]['image']);
        $this->assertContains("sites/{$site->id}/sections/gone.jpg", $data['missing_media']);
        $this->assertSame('https://www.google.com/maps/embed?pb=1', $sections[2]['content']['shared']['embed_url']);

        // the library lists images with their credits
        $hit = collect($data['media'])->firstWhere('file', 'media/hero.jpg');
        $this->assertSame('Image by u from Pixabay', $hit['credit']);
    }

    #[Test]
    public function remote_images_are_downloaded_or_reported(): void
    {
        Http::fake([
            'cdn.example.test/ok.jpg' => Http::response('REMOTE', 200, ['Content-Type' => 'image/jpeg']),
            'cdn.example.test/dead.jpg' => Http::response('', 404),
        ]);
        $site = $this->site();
        $site->homePage->sections()->first()->update(['content' => ['shared' => ['image' => 'https://cdn.example.test/ok.jpg']]]);
        Section::factory()->for($site->homePage)->create(['type' => SectionType::About, 'content' => ['shared' => ['image' => 'https://cdn.example.test/dead.jpg']]]);

        [$data, $zip] = $this->open(app(SiteExporter::class)->export($site));

        $this->assertSame('REMOTE', $zip->getFromName('media/ok.jpg'));
        $this->assertContains('https://cdn.example.test/dead.jpg', $data['missing_media']);
    }

    #[Test]
    public function admin_can_download_the_export(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel('admin');
        $site = $this->site();

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->callAction('export')
            ->assertFileDownloaded('acme-export-'.now()->format('Y-m-d').'.zip');

        Livewire::test(ListSites::class)
            ->callAction(TestAction::make('export')->table($site))
            ->assertFileDownloaded();
    }

    #[Test]
    public function artisan_command_exports_by_slug(): void
    {
        $this->site();

        $this->artisan('site:export', ['slug' => 'acme'])->expectsOutputToContain('Exported acme')->assertSuccessful();
        $this->artisan('site:export', ['slug' => 'nope'])->assertFailed();
        $this->assertNotEmpty(Storage::disk('local')->files('exports'));
    }
}
