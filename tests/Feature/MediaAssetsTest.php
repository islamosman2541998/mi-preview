<?php

namespace Tests\Feature;

use App\Enums\MediaSource;
use App\Enums\SectionType;
use App\Filament\Resources\Sites\Pages\CreateSite;
use App\Filament\Resources\Sites\Pages\EditSite;
use App\Filament\Resources\Sites\RelationManagers\MediaRelationManager;
use App\Filament\Resources\Sites\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Sites\Resources\Pages\RelationManagers\SectionsRelationManager;
use App\Filament\Support\ImagePicker;
use App\Models\Industry;
use App\Models\Media;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use App\Services\MediaLibrary;
use App\Services\Stock\Pexels;
use App\Services\Stock\Pixabay;
use App\Services\Stock\StockPhotos;
use App\Support\Palette;
use Database\Seeders\IndustrySeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class MediaAssetsTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = 'http://app.localhost';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->seed(IndustrySeeder::class);
    }

    private function admin(): static
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel('admin');

        return $this;
    }

    private function photo(int $id = 7, string $original = 'https://images.pexels.com/photos/7/pexels-photo-7.jpeg'): array
    {
        return [
            'id' => $id, 'width' => 4000, 'height' => 2667, 'url' => "https://www.pexels.com/photo/{$id}/",
            'photographer' => 'Jane Roe', 'photographer_url' => 'https://www.pexels.com/@jane', 'avg_color' => '#7A6E5D',
            'alt' => 'Construction site at dusk',
            'src' => ['original' => $original, 'large' => $original.'?h=650', 'medium' => $original.'?h=350'],
        ];
    }

    private function fakePexels(array $photo = []): void
    {
        config(['services.pexels.key' => 'test-key']);
        $photo = $photo ?: $this->photo();

        Http::fake([
            'api.pexels.com/v1/search*' => Http::response(['total_results' => 1, 'page' => 1, 'next_page' => 'https://api.pexels.com/v1/search?page=2', 'photos' => [$photo]]),
            'api.pexels.com/v1/photos/*' => Http::response($photo),
            'images.pexels.com/*' => Http::response('JPEGBYTES', 200, ['Content-Type' => 'image/jpeg']),
        ]);
    }

    private function pngLogo(string $path, array $rgb = [0x1D, 0x4E, 0x89]): void
    {
        $im = imagecreatetruecolor(300, 150);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        imagefilledrectangle($im, 30, 30, 270, 120, imagecolorallocate($im, ...$rgb));
        ob_start();
        imagepng($im);
        Storage::disk('public')->put($path, ob_get_clean());
    }

    private function pixabayHit(int $id = 42, string $large = 'https://pixabay.com/get/g1a2b3_1280.jpg'): array
    {
        return [
            'id' => $id, 'pageURL' => "https://pixabay.com/photos/crane-{$id}/", 'type' => 'photo', 'tags' => 'crane, construction, site',
            'previewURL' => 'https://cdn.pixabay.com/photo/x_150.jpg', 'webformatURL' => 'https://pixabay.com/get/g1a2b3_640.jpg',
            'largeImageURL' => $large, 'imageWidth' => 5000, 'imageHeight' => 3333, 'user_id' => 99, 'user' => 'builder',
        ];
    }

    private function fakePixabay(array $hit = [], int $totalHits = 60): void
    {
        config(['services.pixabay.key' => 'px-key']);
        $hit = $hit ?: $this->pixabayHit();

        Http::fake([
            'pixabay.com/api/*' => function ($request) use ($hit, $totalHits) {
                return Http::response(isset($request['id'])
                    ? ['total' => 1, 'totalHits' => 1, 'hits' => [$hit]]
                    : ['total' => 9000, 'totalHits' => $totalHits, 'hits' => [$hit]]);
            },
            'pixabay.com/get/*' => Http::response('JPEGBYTES', 200, ['Content-Type' => 'image/jpeg']),
        ]);
    }

    // ----------------------------------------------------------------- Pixabay

    #[Test]
    public function pixabay_search_is_normalised_mapped_and_cached(): void
    {
        $this->fakePixabay();

        $this->admin()->getJson(self::ADMIN.'/_picker/stock/pixabay?q=crane&orientation=landscape')
            ->assertOk()
            ->assertJsonPath('photos.0.id', 42)
            ->assertJsonPath('photos.0.photographer', 'builder')
            ->assertJsonPath('photos.0.photographer_url', 'https://pixabay.com/users/builder-99/')
            ->assertJsonPath('photos.0.thumb', 'https://pixabay.com/get/g1a2b3_640.jpg')
            ->assertJsonPath('total', 60)
            ->assertJsonPath('has_more', true);

        $this->getJson(self::ADMIN.'/_picker/stock/pixabay?q=crane&orientation=landscape')->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r['key'] === 'px-key' && $r['q'] === 'crane'
            && $r['orientation'] === 'horizontal' && $r['image_type'] === 'photo' && $r['safesearch'] === 'true');
    }

    #[Test]
    public function pixabay_square_searches_do_not_send_an_orientation(): void
    {
        $this->fakePixabay();

        $this->admin()->getJson(self::ADMIN.'/_picker/stock/pixabay?q=crane&orientation=square')->assertOk();

        Http::assertSent(fn ($r) => ! isset($r['orientation']));
    }

    #[Test]
    public function pixabay_import_downloads_once_with_credit(): void
    {
        $this->fakePixabay();
        $site = Site::factory()->create();

        $media = app(Pixabay::class)->import($site, 42);

        $this->assertSame("sites/{$site->id}/media/pixabay-42.jpg", $media->path);
        $this->assertSame(MediaSource::Pixabay, $media->source);
        $this->assertSame('Image by builder from Pixabay', $media->credit);
        $this->assertSame('https://pixabay.com/photos/crane-42/', $media->meta['url']);
        $this->assertSame('pixabay', $media->meta['provider']);
        Storage::disk('public')->assertExists($media->path);

        app(Pixabay::class)->import($site, 42);
        $this->assertSame(1, $site->media()->count());
    }

    #[Test]
    public function pixabay_import_refuses_other_hosts(): void
    {
        $this->fakePixabay($this->pixabayHit(43, 'https://pixabay.com.evil.example/x.jpg'));

        $this->expectException(RuntimeException::class);
        app(Pixabay::class)->import(Site::factory()->create(), 43);
    }

    #[Test]
    public function pixabay_bad_key_is_reported_in_arabic(): void
    {
        config(['services.pixabay.key' => 'bad']);
        Http::fake(['pixabay.com/*' => Http::response('[ERROR 400] Invalid or missing API key', 400)]);

        $this->admin()->getJson(self::ADMIN.'/_picker/stock/pixabay?q=x')
            ->assertStatus(422)
            ->assertJsonPath('message', 'مفتاح Pixabay غير صالح — راجع ملف ‎.env.');
    }

    #[Test]
    public function unknown_providers_404(): void
    {
        $this->admin()->getJson(self::ADMIN.'/_picker/stock/unsplash?q=x')->assertNotFound();
    }

    #[Test]
    public function picker_lists_only_configured_providers(): void
    {
        config(['services.pixabay.key' => 'k', 'services.pexels.key' => null]);

        $keys = array_keys(app(StockPhotos::class)->configured());

        $this->assertSame(['pixabay'], $keys);
    }

    #[Test]
    public function picker_resolves_pixabay_picks(): void
    {
        $this->fakePixabay();
        $site = Site::factory()->create();

        $this->assertSame(["sites/{$site->id}/media/pixabay-42.jpg"], ImagePicker::resolve($site, [['source' => 'pixabay', 'id' => 42]]));
    }

    // ------------------------------------------------------------------ Pexels

    #[Test]
    public function picker_endpoints_require_login(): void
    {
        $site = Site::factory()->create();

        $this->getJson(self::ADMIN.'/_picker/stock/pexels?q=office')->assertUnauthorized();
        $this->getJson(self::ADMIN."/_picker/sites/{$site->id}/media")->assertUnauthorized();
    }

    #[Test]
    public function pexels_search_without_a_key_explains_how_to_enable_it(): void
    {
        config(['services.pexels.key' => null]);
        Http::fake();

        $this->admin()->getJson(self::ADMIN.'/_picker/stock/pexels?q=office')
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'PEXELS_API_KEY'));

        Http::assertNothingSent();
    }

    #[Test]
    public function pexels_search_returns_simplified_photos_and_is_cached(): void
    {
        $this->fakePexels();

        $this->admin()->getJson(self::ADMIN.'/_picker/stock/pexels?q=construction&orientation=landscape')
            ->assertOk()
            ->assertJsonPath('photos.0.id', 7)
            ->assertJsonPath('photos.0.photographer', 'Jane Roe')
            ->assertJsonPath('photos.0.thumb', 'https://images.pexels.com/photos/7/pexels-photo-7.jpeg?h=350')
            ->assertJsonPath('has_more', true)
            ->assertJsonMissingPath('photos.0.src');

        $this->getJson(self::ADMIN.'/_picker/stock/pexels?q=construction&orientation=landscape')->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'test-key')
            && $r['query'] === 'construction' && $r['orientation'] === 'landscape');
    }

    #[Test]
    public function pexels_errors_are_reported_in_arabic(): void
    {
        config(['services.pexels.key' => 'bad']);
        Http::fake(['api.pexels.com/*' => Http::response(['error' => 'nope'], 401)]);

        $this->admin()->getJson(self::ADMIN.'/_picker/stock/pexels?q=x')
            ->assertStatus(422)
            ->assertJsonPath('message', 'مفتاح Pexels غير صالح — راجع ملف ‎.env.');
    }

    #[Test]
    public function importing_a_photo_downloads_it_once_with_credit(): void
    {
        $this->fakePexels();
        $site = Site::factory()->create();

        $media = app(Pexels::class)->import($site, 7);

        $this->assertSame("sites/{$site->id}/media/pexels-7.jpg", $media->path);
        $this->assertSame(MediaSource::Pexels, $media->source);
        $this->assertSame('Photo by Jane Roe on Pexels', $media->credit);
        $this->assertSame('https://www.pexels.com/photo/7/', $media->meta['url']);
        $this->assertSame('Construction site at dusk', $media->alt_en);
        Storage::disk('public')->assertExists($media->path);
        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://images.pexels.com/') && str_contains($r->url(), 'w=1920'));

        $again = app(Pexels::class)->import($site, 7);
        $this->assertTrue($again->is($media));
        $this->assertSame(1, $site->media()->count());
    }

    #[Test]
    public function import_refuses_images_from_other_hosts(): void
    {
        $this->fakePexels($this->photo(9, 'https://evil.example/x.jpg'));

        $this->expectException(RuntimeException::class);
        app(Pexels::class)->import(Site::factory()->create(), 9);
    }

    #[Test]
    public function library_endpoint_lists_only_that_sites_media(): void
    {
        $site = Site::factory()->create();
        Media::factory()->for($site)->create(['path' => "sites/{$site->id}/media/a.jpg", 'credit' => 'Photo by A on Pexels', 'source' => 'pexels']);
        Media::factory()->for(Site::factory())->create();

        $this->admin()->getJson(self::ADMIN."/_picker/sites/{$site->id}/media")
            ->assertOk()
            ->assertJsonCount(1, 'media')
            ->assertJsonPath('media.0.credit', 'Photo by A on Pexels')
            ->assertJsonPath('media.0.thumb', fn ($url) => str_ends_with($url, "/storage/sites/{$site->id}/media/a.jpg"));
    }

    #[Test]
    public function picker_resolves_pexels_and_library_picks_to_stored_paths(): void
    {
        $this->fakePexels();
        $site = Site::factory()->create();
        $own = Media::factory()->for($site)->create(['path' => "sites/{$site->id}/media/own.jpg"]);

        $paths = ImagePicker::resolve($site, [['source' => 'media', 'id' => $own->id], ['source' => 'pexels', 'id' => 7]]);

        $this->assertSame(["sites/{$site->id}/media/own.jpg", "sites/{$site->id}/media/pexels-7.jpg"], $paths);
    }

    #[Test]
    public function picker_cannot_use_another_sites_media(): void
    {
        $site = Site::factory()->create();
        $foreign = Media::factory()->for(Site::factory())->create();

        $this->expectException(Halt::class);
        ImagePicker::resolve($site, [['source' => 'media', 'id' => $foreign->id]]);
    }

    #[Test]
    public function pick_image_action_fills_a_section_image_field(): void
    {
        $this->fakePexels();
        $this->admin();
        $site = Site::factory()->create();
        $page = Page::factory()->for($site)->create(['is_home' => true]);
        $section = Section::factory()->for($page)->create(['type' => SectionType::About, 'content' => ['ar' => ['title' => 'من نحن']]]);

        Livewire::test(SectionsRelationManager::class, ['ownerRecord' => $page, 'pageClass' => EditPage::class])
            ->mountAction(TestAction::make('edit')->table($section))
            ->mountAction(TestAction::make('pickImage')->schemaComponent('section-fields-about.c.shared.image', schema: 'mountedActionSchema0'))
            ->setActionData(['picked' => [['source' => 'pexels', 'id' => 7]]])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame("sites/{$site->id}/media/pexels-7.jpg", $section->fresh()->content['shared']['image']);
    }

    #[Test]
    public function media_library_can_import_from_pexels(): void
    {
        $this->fakePexels();
        $this->admin();
        $site = Site::factory()->create();

        Livewire::test(MediaRelationManager::class, ['ownerRecord' => $site, 'pageClass' => EditSite::class])
            ->callAction(TestAction::make('importStock')->table(), ['picked' => [['source' => 'pexels', 'id' => 7]]])
            ->assertHasNoErrors();

        $this->assertSame(1, $site->media()->where('source', 'pexels')->count());
    }

    // ------------------------------------------------------------ media library

    #[Test]
    public function images_saved_in_sections_join_the_media_library(): void
    {
        $site = Site::factory()->create();
        $page = Page::factory()->for($site)->create();
        Storage::disk('public')->put("sites/{$site->id}/sections/hero.jpg", 'x');

        Section::factory()->for($page)->create(['type' => SectionType::Hero, 'content' => ['shared' => [
            'image' => "sites/{$site->id}/sections/hero.jpg",
            'secondary_target' => '/projects',
        ]]]);
        Section::factory()->for($page)->create(['type' => SectionType::Gallery, 'content' => ['shared' => [
            'images' => ["sites/{$site->id}/sections/hero.jpg", "sites/{$site->id}/sections/missing.jpg", 'sites/999/sections/other.jpg'],
        ]]]);

        $this->assertSame(["sites/{$site->id}/sections/hero.jpg"], $site->media()->pluck('path')->all());
        $this->assertTrue(app(MediaLibrary::class)->isUsed($site->media()->first()));
    }

    #[Test]
    public function deleting_media_keeps_files_still_used_by_sections(): void
    {
        $this->admin();
        $site = Site::factory()->create();
        $page = Page::factory()->for($site)->create();
        $used = "sites/{$site->id}/media/used.jpg";
        $unused = "sites/{$site->id}/media/unused.jpg";
        Storage::disk('public')->put($used, 'x');
        Storage::disk('public')->put($unused, 'x');
        Section::factory()->for($page)->create(['type' => SectionType::About, 'content' => ['shared' => ['image' => $used]]]);
        $m2 = Media::factory()->for($site)->create(['path' => $unused]);
        $m1 = $site->media()->where('path', $used)->firstOrFail();

        $lw = Livewire::test(MediaRelationManager::class, ['ownerRecord' => $site, 'pageClass' => EditSite::class]);
        $lw->callAction(TestAction::make('delete')->table($m1));
        $lw->callAction(TestAction::make('delete')->table($m2));

        Storage::disk('public')->assertExists($used);
        Storage::disk('public')->assertMissing($unused);
        $this->assertSame(0, $site->media()->count());
    }

    // ---------------------------------------------------------------- logo tools

    #[Test]
    public function edit_page_extracts_the_palette_from_the_logo(): void
    {
        $this->admin();
        $this->pngLogo('sites/logos/acme.png');
        $site = Site::factory()->create(['logo_path' => 'sites/logos/acme.png', 'palette' => ['primary' => '#000000']]);

        $lw = Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])->call('extractLogoPalette');

        [$h] = Palette::hsl($lw->get('data.palette.primary'));
        $this->assertEqualsWithDelta(212, $h, 8);

        $lw->call('save')->assertHasNoFormErrors();
        $this->assertNotSame('#000000', $site->fresh()->palette['primary']);
    }

    #[Test]
    public function wizard_applies_logo_colours_as_soon_as_a_logo_is_uploaded(): void
    {
        $this->admin();
        $industry = Industry::where('key', 'contracting')->firstOrFail();

        $im = imagecreatetruecolor(300, 150);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        imagefilledrectangle($im, 30, 30, 270, 120, imagecolorallocate($im, 0x0E, 0x7C, 0x3A)); // green
        $file = UploadedFile::fake()->createWithContent('logo.png', (function () use ($im) {
            ob_start();
            imagepng($im);

            return ob_get_clean();
        })());

        $lw = Livewire::test(CreateSite::class)
            ->set('data.industry_id', $industry->id)
            ->set('data.logo_path', [$file]);

        [$h] = Palette::hsl($lw->get('data.palette.primary'));
        $this->assertEqualsWithDelta(142, $h, 10, 'primary follows the green logo, not the industry');
    }

    #[Test]
    public function logo_upload_accepts_any_image_mime_type(): void
    {
        $this->admin();
        $site = Site::factory()->create();

        $lw = Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->set('data.logo_path', [UploadedFile::fake()->image('brand.gif', 120, 60)])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = $site->fresh()->logo_path;

        $this->assertStringEndsWith('.gif', $path);
        Storage::disk('public')->assertExists($path);
    }

    #[Test]
    public function background_free_logo_upload_accepts_any_image_mime_type(): void
    {
        $this->admin();
        $site = Site::factory()->create();

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->set('data.logo_nobg_path', [UploadedFile::fake()->image('brand.gif', 120, 60)])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = $site->fresh()->logo_nobg_path;

        $this->assertStringEndsWith('.gif', $path);
        Storage::disk('public')->assertExists($path);
    }

    #[Test]
    public function browser_removed_background_is_stored_and_set_on_the_field(): void
    {
        $this->admin();
        $site = Site::factory()->create();

        $lw = Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->set('logoNobgUpload', UploadedFile::fake()->image('logo-nobg.png', 400, 200))
            ->call('applyLogoNobg');

        $state = $lw->get('data.logo_nobg_path');
        $path = array_values($state)[0];
        $this->assertStringStartsWith('sites/logos/', $path);
        Storage::disk('public')->assertExists($path);

        $lw->call('save')->assertHasNoFormErrors();
        $this->assertSame($path, $site->fresh()->logo_nobg_path);
    }

    #[Test]
    public function non_images_are_rejected_as_background_removed_logo(): void
    {
        $this->admin();
        $site = Site::factory()->create();

        $lw = Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->set('logoNobgUpload', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->call('applyLogoNobg');

        $this->assertEmpty($lw->get('data.logo_nobg_path'));
        $this->assertNull($lw->get('logoNobgUpload'));
    }

    #[Test]
    public function logo_source_is_served_to_the_browser_as_a_data_uri(): void
    {
        $this->admin();
        $this->pngLogo('sites/logos/acme.png');
        $site = Site::factory()->create(['logo_path' => 'sites/logos/acme.png']);

        $lw = Livewire::test(EditSite::class, ['record' => $site->getRouteKey()]);
        $uri = $lw->instance()->logoSource();

        $this->assertStringStartsWith('data:image/png;base64,', $uri);
    }

    #[Test]
    public function remove_bg_fallback_stores_the_result(): void
    {
        $this->admin();
        config(['services.remove_bg.key' => 'rb-key']);
        Http::fake(['api.remove.bg/*' => Http::response('PNGBYTES', 200, ['Content-Type' => 'image/png'])]);
        $this->pngLogo('sites/logos/acme.png');
        $site = Site::factory()->create(['logo_path' => 'sites/logos/acme.png']);

        $lw = Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])->call('removeLogoBgRemote');

        $path = array_values($lw->get('data.logo_nobg_path'))[0];
        $this->assertSame('PNGBYTES', Storage::disk('public')->get($path));
        Http::assertSent(fn ($r) => $r->hasHeader('X-Api-Key', 'rb-key') && str_contains($r->body(), 'preview'));
    }
}
