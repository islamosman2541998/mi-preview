<?php

namespace Tests\Feature;

use App\Ai\Prompt;
use App\Enums\MediaSource;
use App\Enums\SectionType;
use App\Filament\Resources\Sites\Pages\CreateSite;
use App\Filament\Resources\Sites\Pages\EditSite;
use App\Jobs\ImportSocialMedia;
use App\Models\Site;
use App\Models\User;
use App\Services\SiteBuilder;
use App\Services\SocialProfileImporter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class SocialProfileImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config([
            'services.meta.access_token' => 'meta-token',
            'services.meta.page_access_token' => null,
            'services.meta.app_secret' => null,
            'services.meta.page_id' => null,
            'services.meta.instagram_account_id' => '17890001',
            'services.meta.version' => 'v27.0',
            'services.meta.base_url' => 'https://graph.facebook.test',
            'services.meta.media_hosts' => ['fbcdn.test', 'cdninstagram.test'],
        ]);
    }

    #[Test]
    public function facebook_page_data_is_normalised_and_prefills_the_create_wizard(): void
    {
        Http::fake([
            'graph.facebook.test/*/posts*' => Http::response(['data' => $this->facebookPayload()['posts']['data']]),
            'graph.facebook.test/*' => Http::response($this->facebookPayload()),
            'scontent.fbcdn.test/*' => Http::response('LOGO', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $admin = User::factory()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        $component = Livewire::test(CreateSite::class)
            ->fillForm(['source_url' => 'https://facebook.com/acme.controls'])
            ->call('importSocialProfile')
            ->assertSet('data.company_name_ar', 'Acme Controls')
            ->assertSet('data.company_name_en', 'Acme Controls')
            ->assertSet('data.slug', 'acme-controls')
            ->assertSet('data.phone', '+20 100 123 4567')
            ->assertSet('data.email', 'hello@acme.test')
            ->assertSet('data.socials.facebook', 'https://www.facebook.com/acme.controls');

        $this->assertSame($component->get('data.logo_path'), $component->get('data.logo_nobg_path'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v27.0/acme.controls')
            && $request->hasHeader('Authorization', 'Bearer meta-token'));
        $this->assertCount(1, Storage::disk('public')->allFiles('sites/logos'));
    }

    #[Test]
    public function an_existing_site_can_reimport_social_data_and_queue_all_media(): void
    {
        Http::fake([
            'graph.facebook.test/*/posts*' => Http::response(['data' => $this->facebookPayload()['posts']['data']]),
            'graph.facebook.test/*' => Http::response($this->facebookPayload()),
            'scontent.fbcdn.test/*' => Http::response('LOGO', 200, ['Content-Type' => 'image/jpeg']),
        ]);
        Queue::fake();

        $admin = User::factory()->create();
        $site = Site::factory()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->fillForm(['source_url' => 'https://facebook.com/acme.controls'])
            ->call('importSocialProfile')
            ->assertSet('data.phone', '+20 100 123 4567')
            ->assertSet('data.email', 'hello@acme.test')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(2, $site->fresh()->source_data['posts']);
        Queue::assertPushed(ImportSocialMedia::class, fn (ImportSocialMedia $job) => $job->siteId === $site->id);
    }

    #[Test]
    public function facebook_profile_php_links_use_the_numeric_page_id(): void
    {
        Http::fake([
            'graph.facebook.test/*/posts*' => Http::response(['data' => $this->facebookPayload()['posts']['data']]),
            'graph.facebook.test/*' => Http::response($this->facebookPayload()),
        ]);

        $profile = app(SocialProfileImporter::class)
            ->fetch('https://www.facebook.com/profile.php?id=61581634155182');

        $this->assertSame('https://www.facebook.com/profile.php?id=61581634155182', $profile['url']);
        $this->assertSame('61581634155182', $profile['username']);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v27.0/61581634155182')
            && ! str_contains($request->url(), '/profile.php'));
    }

    #[Test]
    public function inaccessible_facebook_pages_show_the_pages_available_to_the_meta_account(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/me/accounts')) {
                return Http::response(['data' => [[
                    'id' => '981188461755574',
                    'name' => 'Islam Web Studio',
                    'access_token' => 'managed-page-token',
                ]]]);
            }

            return Http::response(['error' => [
                'message' => "Unsupported get request. Object with ID '61581634155182' does not exist or is missing permissions.",
                'code' => 100,
            ]], 400);
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Islam Web Studio (981188461755574)');

        app(SocialProfileImporter::class)
            ->fetch('https://www.facebook.com/profile.php?id=61581634155182');
    }

    #[Test]
    public function facebook_basic_data_remains_available_when_post_permission_is_missing(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/posts')) {
                return Http::response(['error' => [
                    'message' => 'Invalid OAuth 2.0 Access Token',
                    'code' => 190,
                ]], 400);
            }

            return Http::response($this->facebookPayload());
        });

        $profile = app(SocialProfileImporter::class)->fetch('https://facebook.com/acme.controls');

        $this->assertSame('Acme Controls', $profile['name']);
        $this->assertSame([], $profile['posts']);
        $this->assertStringContainsString('Page Public Content Access', $profile['warnings'][0]);
    }

    #[Test]
    public function managed_facebook_page_posts_are_read_with_the_page_token_not_the_system_user_token(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/me/accounts')) {
                return Http::response(['data' => [[
                    'id' => '123',
                    'name' => 'Acme Controls',
                    'username' => 'acme.controls',
                    'access_token' => 'managed-page-token',
                ]]]);
            }

            if (str_contains($request->url(), '/posts')) {
                // Graph rejects a system-user token on /posts; only the Page token works.
                return $request->hasHeader('Authorization', 'Bearer managed-page-token')
                    ? Http::response(['data' => $this->facebookPayload()['posts']['data']])
                    : Http::response(['error' => ['message' => 'Invalid OAuth 2.0 Access Token', 'code' => 190]], 400);
            }

            return Http::response($this->facebookPayload());
        });

        $profile = app(SocialProfileImporter::class)->fetch('https://facebook.com/acme.controls');

        $this->assertCount(2, $profile['posts']);
        $this->assertSame([], $profile['warnings']);
    }

    #[Test]
    public function public_pages_outside_the_meta_account_explain_the_page_public_content_access_requirement(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/me/accounts')) {
                return Http::response(['data' => [[
                    'id' => '981188461755574',
                    'name' => 'Islam Web Studio',
                    'access_token' => 'managed-page-token',
                ]]]);
            }

            return Http::response(['error' => [
                'message' => "(#10) This endpoint requires the 'pages_read_engagement' permission or the 'Page Public Content Access' feature or the 'Page Public Metadata Access' feature.",
                'code' => 10,
            ]], 400);
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Islam Web Studio (981188461755574)');

        app(SocialProfileImporter::class)->fetch('https://facebook.com/cocacola');
    }

    #[Test]
    public function instagram_business_discovery_maps_reels_to_thumbnails(): void
    {
        Http::fake(['graph.facebook.test/*' => Http::response([
            'business_discovery' => [
                'username' => 'sparkcontrols',
                'name' => 'Spark Controls',
                'biography' => 'Control panels and automation',
                'website' => 'https://spark.test',
                'profile_picture_url' => 'https://scontent.cdninstagram.test/avatar.jpg',
                'media' => ['data' => [[
                    'caption' => 'PLC panel for a factory',
                    'media_type' => 'VIDEO',
                    'media_url' => 'https://scontent.cdninstagram.test/video.mp4',
                    'thumbnail_url' => 'https://scontent.cdninstagram.test/thumb.jpg',
                    'permalink' => 'https://instagram.com/reel/abc',
                    'timestamp' => '2026-01-02T10:00:00+0000',
                ]]],
            ],
        ])]);

        $profile = app(SocialProfileImporter::class)->fetch('https://www.instagram.com/sparkcontrols/');

        $this->assertSame('instagram', $profile['platform']);
        $this->assertSame('Spark Controls', $profile['name']);
        $this->assertSame('https://scontent.cdninstagram.test/thumb.jpg', $profile['posts'][0]['image_url']);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v27.0/17890001')
            && str_contains((string) $request['fields'], 'business_discovery.username(sparkcontrols)')
            && str_contains((string) $request['fields'], 'media.limit(36)'));
    }

    #[Test]
    public function instagram_bio_prefills_contact_fields_missing_from_business_discovery(): void
    {
        Http::fake(['graph.facebook.test/*' => Http::response([
            'business_discovery' => [
                'username' => 'studio',
                'name' => 'Studio',
                'biography' => "Interior design\nالعنوان: التجمع الخامس، القاهرة\nhello@studio.test\nWhatsApp: https://wa.me/201001234567",
                'website' => 'https://maps.app.goo.gl/example',
                'media' => ['data' => []],
            ],
        ])]);

        $profile = app(SocialProfileImporter::class)->fetch('https://instagram.com/studio');

        $this->assertSame('hello@studio.test', $profile['email']);
        $this->assertSame('+201001234567', $profile['whatsapp']);
        $this->assertSame('التجمع الخامس، القاهرة', $profile['address']);
        $this->assertSame('https://maps.app.goo.gl/example', $profile['map_url']);
    }

    #[Test]
    public function instagram_permission_error_explains_the_required_meta_access(): void
    {
        Http::fake(['graph.facebook.test/*' => Http::response(['error' => [
            'message' => '(#10) Application does not have permission for this action',
            'code' => 10,
        ]], 400)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('instagram_basic');

        app(SocialProfileImporter::class)->fetch('https://instagram.com/sparkcontrols');
    }

    #[Test]
    public function a_failed_connection_to_meta_is_reported_as_a_network_problem(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect to graph.facebook.com port 443'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('تعذّر الاتصال بخوادم Meta');

        app(SocialProfileImporter::class)->fetch('https://instagram.com/sparkcontrols');
    }

    #[Test]
    public function instagram_account_is_discovered_from_the_page_and_calls_use_app_secret_proof(): void
    {
        config([
            'services.meta.access_token' => 'user-token',
            'services.meta.page_access_token' => 'page-token',
            'services.meta.page_id' => '981188461755574',
            'services.meta.instagram_account_id' => null,
            'services.meta.app_secret' => 'rotated-secret',
        ]);
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/981188461755574')) {
                return Http::response(['instagram_business_account' => ['id' => '17895555']]);
            }

            return Http::response(['business_discovery' => [
                'username' => 'sparkcontrols',
                'name' => 'Spark Controls',
                'media' => ['data' => []],
            ]]);
        });

        $profile = app(SocialProfileImporter::class)->fetch('https://instagram.com/sparkcontrols');

        $this->assertSame('Spark Controls', $profile['name']);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/981188461755574')
            && $request->hasHeader('Authorization', 'Bearer user-token'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/17895555')
            && $request->hasHeader('Authorization', 'Bearer page-token')
            && $request['appsecret_proof'] === hash_hmac('sha256', 'page-token', 'rotated-secret'));
    }

    #[Test]
    public function imported_posts_become_library_media_section_images_and_ai_context(): void
    {
        Http::fake(['scontent.fbcdn.test/*' => Http::response('JPEG', 200, ['Content-Type' => 'image/jpeg'])]);
        $site = Site::factory()->create([
            'source_url' => 'https://www.facebook.com/acme.controls',
            'source_data' => $this->facebookProfile(),
        ]);
        app(SiteBuilder::class)->build($site);

        $count = app(SocialProfileImporter::class)->importMediaAndSeed($site);
        $site->refresh();

        $this->assertSame(2, $count);
        $this->assertSame(2, $site->media()->where('source', MediaSource::Facebook)->count());
        $hero = $site->sections()->where('type', SectionType::Hero)->firstOrFail();
        $services = $site->sections()->where('type', SectionType::Services)->firstOrFail();
        $this->assertNotEmpty($hero->content['shared']['image']);
        $this->assertSame('Factory control panel installation', $services->content['ar']['items'][0]['description']);

        $prompt = Prompt::user($site, $site->sections()->with('page')->get(), null, false);
        $this->assertStringContainsString('Verified source material', $prompt);
        $this->assertStringContainsString('Factory control panel installation', $prompt);
    }

    #[Test]
    public function more_than_twelve_social_posts_are_imported_and_given_to_the_ai(): void
    {
        Http::fake(['scontent.fbcdn.test/*' => Http::response('JPEG', 200, ['Content-Type' => 'image/jpeg'])]);
        $posts = array_map(fn (int $i) => [
            'caption' => "Distinct project caption {$i}",
            'image_url' => "https://scontent.fbcdn.test/project-{$i}.jpg",
            'permalink' => "https://facebook.com/acme/posts/{$i}",
            'published_at' => '2026-01-01T10:00:00+0000',
        ], range(1, 20));
        $site = Site::factory()->create(['source_data' => $this->facebookProfile() + ['posts' => $posts]]);
        // Array union keeps the original posts key, so set the richer list explicitly.
        $source = $site->source_data;
        $source['posts'] = $posts;
        $site->update(['source_data' => $source]);
        app(SiteBuilder::class)->build($site);

        $this->assertSame(20, app(SocialProfileImporter::class)->importMediaAndSeed($site));
        $this->assertSame(20, $site->media()->where('source', MediaSource::Facebook)->count());

        $prompt = Prompt::user($site->fresh(), $site->sections()->with('page')->get(), null, false);
        $this->assertStringContainsString('Distinct project caption 20', $prompt);
    }

    #[Test]
    public function downloaded_social_photos_fill_every_generated_item_and_large_galleries(): void
    {
        $site = Site::factory()->create(['source_data' => [
            'platform' => 'facebook',
            'posts' => array_fill(0, 8, ['caption' => 'Project', 'image_url' => 'https://scontent.fbcdn.test/project.jpg']),
        ]]);
        app(SiteBuilder::class)->build($site);

        foreach (range(1, 18) as $i) {
            $site->media()->create([
                'path' => "sites/{$site->id}/social/{$i}.jpg",
                'source' => MediaSource::Facebook,
                'meta' => ['caption' => "Project {$i}"],
            ]);
        }

        $services = $site->sections()->where('type', SectionType::Services)->firstOrFail();
        $content = $services->content;
        $content['ar']['items'] = $content['en']['items'] = array_fill(0, 8, ['title' => 'x', 'description' => 'x']);
        $content['shared']['items'] = array_fill(0, 8, ['image' => null, 'icon' => null]);
        $services->update(['content' => $content]);

        app(SocialProfileImporter::class)->seedFromLibrary($site);

        $images = collect($services->fresh()->content['shared']['items'])->pluck('image')->filter();
        $gallery = $site->sections()->where('type', SectionType::Gallery)->firstOrFail();
        $this->assertCount(8, $images);
        $this->assertCount(8, $images->unique());
        $this->assertCount(16, $gallery->fresh()->content['shared']['images']);
    }

    /** MySQL rejects values longer than the varchar(255) alt / credit columns (SQLite does not). */
    #[Test]
    public function long_post_captions_fit_the_media_columns_and_stay_whole_in_meta(): void
    {
        Http::fake(['scontent.fbcdn.test/*' => Http::response('JPEG', 200, ['Content-Type' => 'image/jpeg'])]);
        $caption = str_repeat('لوحة تحكم صناعية مع تركيب كامل وتشغيل في المصنع. ', 40);
        $profile = $this->facebookProfile();
        $profile['posts'][0]['caption'] = $caption;
        $site = Site::factory()->create(['source_url' => 'https://www.facebook.com/acme.controls', 'source_data' => $profile]);
        app(SiteBuilder::class)->build($site);

        app(SocialProfileImporter::class)->importMediaAndSeed($site);

        $media = $site->media()->where('source', MediaSource::Facebook)->orderBy('id')->firstOrFail();
        $this->assertLessThanOrEqual(255, mb_strlen($media->alt_ar));
        $this->assertLessThanOrEqual(255, mb_strlen($media->alt_en));
        $this->assertStringStartsWith('لوحة تحكم صناعية', $media->alt_ar);
        $this->assertGreaterThan(255, mb_strlen($media->meta['caption']), 'full caption kept for the AI and the admin');
    }

    #[Test]
    public function post_links_and_unsupported_hosts_are_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        app(SocialProfileImporter::class)->fetch('https://instagram.com/reel/abc');
    }

    private function facebookPayload(): array
    {
        return [
            'id' => '123',
            'name' => 'Acme Controls',
            'about' => 'Industrial automation and control panels',
            'phone' => '+20 100 123 4567',
            'emails' => ['hello@acme.test'],
            'website' => 'https://acme.test',
            'location' => ['street' => 'Industrial Zone', 'city' => 'Cairo', 'country' => 'Egypt'],
            'picture' => ['data' => ['url' => 'https://scontent.fbcdn.test/logo.jpg']],
            'posts' => ['data' => [[
                'message' => 'Factory control panel installation',
                'full_picture' => 'https://scontent.fbcdn.test/project-1.jpg',
                'created_time' => '2026-01-01T10:00:00+0000',
                'permalink_url' => 'https://facebook.com/acme/posts/1',
            ], [
                'message' => 'PLC programming and commissioning',
                'full_picture' => 'https://scontent.fbcdn.test/project-2.jpg',
                'created_time' => '2026-01-02T10:00:00+0000',
                'permalink_url' => 'https://facebook.com/acme/posts/2',
            ]]],
        ];
    }

    private function facebookProfile(): array
    {
        $payload = $this->facebookPayload();

        return [
            'platform' => 'facebook',
            'url' => 'https://www.facebook.com/acme.controls',
            'username' => 'acme.controls',
            'name' => $payload['name'],
            'biography' => $payload['about'],
            'posts' => array_map(fn ($post) => [
                'caption' => $post['message'],
                'image_url' => $post['full_picture'],
                'permalink' => $post['permalink_url'],
                'published_at' => $post['created_time'],
            ], $payload['posts']['data']),
        ];
    }
}
