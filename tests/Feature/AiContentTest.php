<?php

namespace Tests\Feature;

use App\Ai\AiException;
use App\Ai\AiProviders;
use App\Ai\AiRuns;
use App\Ai\ContentGenerator;
use App\Ai\GenerationResult;
use App\Ai\Prompt;
use App\Ai\SectionSchema;
use App\Ai\TextGenerator;
use App\Enums\SectionType;
use App\Filament\Resources\Sites\Pages\CreateSite;
use App\Filament\Resources\Sites\Pages\EditSite;
use App\Filament\Resources\Sites\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Sites\Resources\Pages\RelationManagers\SectionsRelationManager;
use App\Filament\Widgets\AiRunProgress;
use App\Jobs\GenerateSiteContent;
use App\Models\AiRun;
use App\Models\Industry;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use App\Support\ServiceIcons;
use Database\Seeders\IndustrySeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiContentTest extends TestCase
{
    use RefreshDatabase;

    /** What the fake model returns (closure gets the user prompt) and what it was asked. */
    private \Closure $reply;

    private array $calls = [];

    private bool $aiConfigured = true;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(IndustrySeeder::class);
        config(['services.pixabay.key' => null, 'services.pexels.key' => null]);

        $this->reply = fn () => ['seo' => [], 'sections' => []];
        $test = $this;

        $this->app->instance(AiProviders::class, new AiProviders([$this->fake('claude'), $this->fake('gemini', configured: false)]));
    }

    /** A fake provider that records calls in $this->calls (with its key) and answers with $this->reply. */
    private function fake(string $key, bool $configured = true): TextGenerator
    {
        return new class($this, $key, $configured) implements TextGenerator
        {
            public function __construct(private AiContentTest $test, private string $k, private bool $on) {}

            public function key(): string
            {
                return $this->k;
            }

            public function label(): string
            {
                return ucfirst($this->k).' (fake)';
            }

            public function configured(): bool
            {
                return $this->on && $this->test->aiConfigured();
            }

            public function narrowSchema(): bool
            {
                return $this->k === 'gemini';
            }

            public function generate(string $system, string $user, array $schema, ?callable $onProgress = null): GenerationResult
            {
                return $this->test->fakeGenerate($system, $user, $schema, $onProgress, $this->k);
            }
        };
    }

    private function enableGemini(): void
    {
        $this->app->instance(AiProviders::class, new AiProviders([$this->fake('claude'), $this->fake('gemini')]));
    }

    public function aiConfigured(): bool
    {
        return $this->aiConfigured;
    }

    public function fakeGenerate(string $system, string $user, array $schema, ?callable $onProgress, string $provider = 'claude'): GenerationResult
    {
        $this->calls[] = compact('system', 'user', 'schema', 'provider');
        $onProgress && $onProgress(5000, true);
        $data = ($this->reply)($user);

        if ($data instanceof \Throwable) {
            throw $data;
        }

        return new GenerationResult($data, $provider === 'gemini' ? 'gemini-3.8-flash' : 'claude-opus-5-5', 3000, 9000);
    }

    /** A site with a home page: hero, about, projects, cta. */
    private function site(): Site
    {
        $site = Site::factory()->create([
            'company_name_ar' => 'أكمي للمقاولات', 'company_name_en' => 'Acme Contracting',
            'industry_id' => Industry::where('key', 'contracting')->value('id'),
            'short_description' => 'مقاولات عامة وتشطيبات في القاهرة الجديدة منذ 2009',
        ]);
        $page = Page::factory()->for($site)->create(['slug' => 'home', 'is_home' => true, 'title_ar' => 'الرئيسية', 'title_en' => 'Home']);

        foreach ([SectionType::Hero, SectionType::About, SectionType::Projects, SectionType::Cta] as $i => $type) {
            Section::factory()->for($page)->create(['type' => $type, 'sort' => $i, 'content' => ['ar' => ['title' => 'قديم'], 'en' => ['title' => 'Old']]]);
        }

        return $site->fresh();
    }

    private function sectionOf(Site $site, SectionType $type): Section
    {
        return $site->sections()->where('type', $type)->firstOrFail();
    }

    /** A valid model reply for every section of the site. */
    private function replyFor(Site $site): array
    {
        $out = [];
        foreach ($site->sections()->get() as $s) {
            $out[] = ['id' => $s->id, 'type' => $s->type->value, 'content' => match ($s->type) {
                SectionType::Hero => [
                    'ar' => ['eyebrow' => 'مقاولات عامة — القاهرة', 'title' => 'نبني ونسلّم في موعدنا', 'subtitle' => 'خرسانة وتشطيبات.', 'primary_cta' => 'اطلب عرض سعر', 'secondary_cta' => 'مشاريعنا'],
                    'en' => ['eyebrow' => 'General contracting — Cairo', 'title' => 'Built and handed over on time', 'subtitle' => 'Concrete and finishing.', 'primary_cta' => 'Get a quote', 'secondary_cta' => 'Our projects'],
                    'image_query' => 'construction site concrete',
                ],
                SectionType::About => [
                    'ar' => ['title' => 'من نحن', 'body' => 'نص'], 'en' => ['title' => 'About us', 'body' => 'Text'],
                    'shared' => ['since' => 2009],
                    'lists' => ['points' => [['ar' => 'نقطة', 'en' => 'Point']]],
                    'image_query' => 'construction team',
                ],
                SectionType::Projects => [
                    'ar' => ['title' => 'مشاريعنا', 'intro' => 'مختارات'], 'en' => ['title' => 'Our projects', 'intro' => 'Selected'],
                    'items' => [
                        ['title_ar' => 'برج سكني', 'title_en' => 'Residential tower', 'location_ar' => 'التجمع', 'location_en' => 'New Cairo', 'category_ar' => 'سكني', 'category_en' => 'Residential', 'description_ar' => 'و', 'description_en' => 'd', 'year' => 2023, 'image_query' => 'residential tower'],
                        ['title_ar' => 'مول', 'title_en' => 'Mall', 'location_ar' => 'الشيخ زايد', 'location_en' => 'Sheikh Zayed', 'category_ar' => 'تجاري', 'category_en' => 'Commercial', 'description_ar' => 'و', 'description_en' => 'd', 'year' => 2022, 'image_query' => 'shopping mall exterior'],
                    ],
                    'image_query' => 'building projects',
                ],
                SectionType::Cta => ['ar' => ['title' => 'جاهز تبدأ؟', 'body' => 'كلمنا', 'button' => 'واتساب'], 'en' => ['title' => 'Ready?', 'body' => 'Talk to us', 'button' => 'WhatsApp']],
                default => [],
            }];
        }

        return ['seo' => ['title_ar' => 'أكمي للمقاولات', 'title_en' => 'Acme Contracting', 'description_ar' => 'وصف', 'description_en' => 'Description'], 'sections' => $out];
    }

    private function runNow(Site $site, ?array $ids = null, array $options = []): AiRun
    {
        Queue::fake();
        $run = app(AiRuns::class)->start($site, $ids, $options);
        (new GenerateSiteContent($run->id))->handle(app(ContentGenerator::class));

        return $run->fresh();
    }

    // ---------------------------------------------------------------- schema

    #[Test]
    public function response_schema_is_strict_everywhere_and_never_asks_for_images(): void
    {
        $schema = SectionSchema::response();
        $variants = $schema['properties']['sections']['items']['anyOf'];

        $this->assertCount(count(SectionType::cases()), $variants);
        $this->assertSame($schema, SectionSchema::response(), 'stable → compiled once and cached by the API');

        $walk = function (array $node, string $path) use (&$walk) {
            if (($node['type'] ?? null) === 'object') {
                $this->assertFalse($node['additionalProperties'], "{$path}: additionalProperties must be false");
                $props = (array) $node['properties'];
                $this->assertSame(array_keys($props), $node['required'], "{$path}: every property required");
                $this->assertEmpty(array_intersect(array_keys($props), ['image', 'images', 'embed_url', 'secondary_target']), "{$path}: no images/links");
                foreach ($props as $k => $child) {
                    $walk($child, "{$path}.{$k}");
                }
            }
            foreach ($node['anyOf'] ?? [] as $i => $v) {
                $walk($v, "{$path}|{$i}");
            }
            if (isset($node['items'])) {
                $walk($node['items'], "{$path}[]");
            }
        };
        $walk($schema, '$');

        $hero = SectionSchema::forType(SectionType::Hero);
        $this->assertArrayHasKey('image_query', $hero['properties']);
        $this->assertArrayHasKey('image_query', SectionSchema::forType(SectionType::Projects)['properties']['items']['items']['properties']);
        $this->assertArrayNotHasKey('image_query', SectionSchema::forType(SectionType::Testimonials)['properties']);
        $this->assertSame('number', SectionSchema::forType(SectionType::Stats)['properties']['items']['items']['properties']['value']['type']);
    }

    #[Test]
    public function merge_replaces_text_but_keeps_images_and_links(): void
    {
        $existing = [
            'ar' => ['title' => 'قديم', 'items' => [['title' => 'أ'], ['title' => 'ب']]],
            'en' => ['title' => 'Old', 'items' => [['title' => 'A'], ['title' => 'B']]],
            'shared' => ['items' => [['image' => 'sites/1/a.jpg', 'year' => 2001], ['image' => 'sites/1/b.jpg', 'year' => 2002]]],
        ];
        $ai = ['ar' => ['title' => 'جديد', 'intro' => 'م'], 'en' => ['title' => 'New', 'intro' => 'I'], 'items' => [
            ['title_ar' => 'س', 'title_en' => 'X', 'year' => 2024, 'image_query' => 'q1'],
            ['title_ar' => 'ص', 'title_en' => 'Y', 'year' => 2023, 'image_query' => 'q2'],
            ['title_ar' => 'ع', 'title_en' => 'Z', 'year' => 2022, 'image_query' => 'q3'],
        ], 'image_query' => 'projects'];

        $content = ContentGenerator::merge(SectionType::Projects, $existing, $ai);

        $this->assertSame('جديد', $content['ar']['title']);
        $this->assertSame('Z', $content['en']['items'][2]['title']);
        $this->assertSame('sites/1/a.jpg', $content['shared']['items'][0]['image'], 'image kept by index');
        $this->assertNull($content['shared']['items'][2]['image']);
        $this->assertSame(2024, $content['shared']['items'][0]['year']);
        $this->assertSame(['q1', 'q2', 'q3'], $content['ai']['item_queries']);

        $hero = ContentGenerator::merge(SectionType::Hero,
            ['ar' => ['title' => 'x'], 'shared' => ['image' => 'sites/1/h.jpg', 'secondary_target' => '/projects']],
            ['ar' => ['title' => 'عنوان'], 'en' => ['title' => 'Title'], 'image_query' => 'q']);
        $this->assertSame('/projects', $hero['shared']['secondary_target']);
        $this->assertSame('sites/1/h.jpg', $hero['shared']['image']);
    }

    #[Test]
    public function prompt_carries_the_facts_and_the_section_list(): void
    {
        $site = $this->site();
        $prompt = Prompt::user($site, $site->sections()->with('page')->get(), 'أسلوب رسمي', rewrite: false);

        $this->assertStringContainsString('أكمي للمقاولات', $prompt);
        $this->assertStringContainsString('القاهرة الجديدة', $prompt);
        $this->assertStringContainsString('"type": "projects"', $prompt);
        $this->assertStringContainsString('أسلوب رسمي', $prompt);
        $this->assertStringNotContainsString('"current"', $prompt);
        $this->assertStringContainsString('agency clichés', Prompt::SYSTEM);
    }

    // ------------------------------------------------------------------ runs

    #[Test]
    public function whole_site_run_writes_every_section_and_the_seo(): void
    {
        $site = $this->site();
        $this->reply = fn () => $this->replyFor($site);

        $run = $this->runNow($site);

        $this->assertSame(AiRun::DONE, $run->status);
        $this->assertSame(100, $run->progress);
        $this->assertSame(4, $run->usage['sections']);
        $this->assertSame(0.192, $run->usage['cost_usd'], '3000 in × $4 + 9000 out × $20 per MTok');
        $this->assertSame('نبني ونسلّم في موعدنا', $this->sectionOf($site, SectionType::Hero)->content['ar']['title']);
        $this->assertSame('Mall', $this->sectionOf($site, SectionType::Projects)->content['en']['items'][1]['title']);
        $this->assertSame(2009, $this->sectionOf($site, SectionType::About)->content['shared']['since']);
        $this->assertSame('Acme Contracting', $site->fresh()->seo['title_en']);

        // the preview renders the generated copy and SEO meta
        $this->get('http://'.$site->host())->assertOk()
            ->assertSee('نبني ونسلّم في موعدنا')
            ->assertSee('<meta name="description" content="وصف">', false);
    }

    #[Test]
    public function entries_for_unknown_ids_or_wrong_types_are_ignored(): void
    {
        $site = $this->site();
        $hero = $this->sectionOf($site, SectionType::Hero);
        $other = Section::factory()->create(['type' => SectionType::Cta]); // another site's section
        $this->reply = fn () => ['seo' => [], 'sections' => [
            ['id' => $hero->id, 'type' => 'cta', 'content' => ['ar' => ['title' => 'خطأ']]],
            ['id' => $other->id, 'type' => 'cta', 'content' => ['ar' => ['title' => 'اختراق'], 'en' => ['title' => 'x']]],
        ]];

        $run = $this->runNow($site);

        $this->assertSame('قديم', $hero->fresh()->content['ar']['title']);
        $this->assertNotSame('اختراق', $other->fresh()->content['ar']['title'] ?? null);
        $this->assertStringContainsString('4 قسم لم يصل', $run->stage);
    }

    #[Test]
    public function section_run_rewrites_only_that_section_with_its_current_text(): void
    {
        $site = $this->site();
        $site->update(['seo' => ['title_ar' => 'يدوي']]);
        $cta = $this->sectionOf($site, SectionType::Cta);
        $this->reply = fn () => ['seo' => ['title_ar' => 'لا'], 'sections' => array_values(array_filter(
            $this->replyFor($site)['sections'], fn ($s) => $s['id'] === $cta->id
        ))];

        $run = $this->runNow($site, [$cta->id], ['instructions' => 'أقصر']);

        $this->assertSame('جاهز تبدأ؟', $cta->fresh()->content['ar']['title']);
        $this->assertSame('قديم', $this->sectionOf($site, SectionType::Hero)->content['ar']['title']);
        $this->assertSame('يدوي', $site->fresh()->seo['title_ar'], 'section runs leave SEO alone');
        $this->assertStringContainsString('"current"', $this->calls[0]['user']);
        $this->assertStringContainsString('أقصر', $this->calls[0]['user']);
        $this->assertSame(1, $run->usage['sections']);
    }

    #[Test]
    public function empty_image_fields_are_filled_from_stock_photos_without_duplicates(): void
    {
        $site = $this->site();
        $about = $this->sectionOf($site, SectionType::About);
        $about->update(['content' => ['shared' => ['image' => 'sites/'.$site->id.'/mine.jpg']]]);
        $this->reply = fn () => $this->replyFor($site);

        $this->fakePixabay();

        $run = $this->runNow($site, null, ['fill_images' => true]);

        $hero = $this->sectionOf($site, SectionType::Hero)->content;
        $projects = $this->sectionOf($site, SectionType::Projects)->content;
        $used = [$hero['shared']['image'], $projects['shared']['items'][0]['image'], $projects['shared']['items'][1]['image']];

        $this->assertSame(AiRun::DONE, $run->status, (string) $run->error);
        $this->assertSame("sites/{$site->id}/media/pixabay-100.jpg", $hero['shared']['image']);
        $this->assertCount(3, array_unique($used), 'no photo used twice');
        $this->assertSame('sites/'.$site->id.'/mine.jpg', $this->sectionOf($site, SectionType::About)->content['shared']['image'], 'hand-picked image kept');
        // hero, about (second photo), two projects — the cta reply carries no image_query, so it stays empty
        $this->assertSame(4, $site->media()->where('source', 'pixabay')->count());
    }

    /** Every Pixabay search returns the same photos 100…(99 + $perSearch); downloads succeed. */
    private function fakePixabay(int $perSearch = 8): void
    {
        config(['services.pixabay.key' => 'k']);
        $hits = fn (int $from, int $count) => array_map(fn ($i) => [
            'id' => $i, 'pageURL' => "https://pixabay.com/p/{$i}", 'tags' => 't', 'webformatURL' => "https://pixabay.com/get/{$i}_640.jpg",
            'largeImageURL' => "https://pixabay.com/get/{$i}_1280.jpg", 'imageWidth' => 1, 'imageHeight' => 1, 'user' => 'u', 'user_id' => 1,
        ], range($from, $from + $count - 1));
        Http::fake([
            'pixabay.com/api/*' => function ($r) use ($hits, $perSearch) {
                return isset($r['id'])
                    ? Http::response(['totalHits' => 1, 'hits' => $hits((int) $r['id'], 1)])
                    : Http::response(['totalHits' => $perSearch, 'hits' => $hits(100, $perSearch)]);
            },
            'pixabay.com/get/*' => Http::response('IMG', 200, ['Content-Type' => 'image/jpeg']),
        ]);
    }

    #[Test]
    public function schema_lets_the_model_pick_a_service_icon(): void
    {
        $item = SectionSchema::forType(SectionType::Services)['properties']['items']['items']['properties'];

        $this->assertSame(ServiceIcons::KEYS, $item['icon']['enum']);
        $this->assertArrayHasKey('image_query', $item);

        foreach ([SectionType::Stats, SectionType::Cta, SectionType::Faq, SectionType::Booking] as $type) {
            $this->assertArrayHasKey('image_query', SectionSchema::forType($type)['properties'], $type->value);
        }
    }

    #[Test]
    public function regenerating_keeps_photos_in_new_slots(): void
    {
        $cta = ContentGenerator::merge(SectionType::Cta,
            ['ar' => ['title' => 'قديم'], 'shared' => ['image' => 'sites/1/bg.jpg']],
            ['ar' => ['title' => 'جديد', 'body' => 'ن', 'button' => 'ز'], 'en' => ['title' => 'New', 'body' => 'b', 'button' => 'x'], 'image_query' => 'q']);
        $this->assertSame('sites/1/bg.jpg', $cta['shared']['image']);
        $this->assertSame('جديد', $cta['ar']['title']);

        $about = ContentGenerator::merge(SectionType::About,
            ['ar' => ['title' => 'قديم'], 'shared' => ['image' => 'sites/1/a.jpg', 'image2' => 'sites/1/b.jpg']],
            ['ar' => ['title' => 'جديد', 'body' => 'ن'], 'en' => ['title' => 'New', 'body' => 'b'], 'shared' => ['since' => 2009], 'image_query' => 'q']);
        $this->assertSame('sites/1/b.jpg', $about['shared']['image2']);

        $services = ContentGenerator::merge(SectionType::Services,
            ['ar' => ['items' => [['title' => 'أ']]], 'en' => ['items' => [['title' => 'A']]], 'shared' => ['items' => [['image' => 'sites/1/s.jpg', 'icon' => 'tools']]]],
            ['ar' => ['title' => 'خدمات', 'intro' => 'م'], 'en' => ['title' => 'Services', 'intro' => 'i'], 'items' => [
                ['title_ar' => 'حفر', 'title_en' => 'Excavation', 'description_ar' => 'و', 'description_en' => 'd', 'icon' => 'crane', 'image_query' => 'excavator'],
                ['title_ar' => 'طرق', 'title_en' => 'Roads', 'description_ar' => 'و', 'description_en' => 'd', 'icon' => 'not-an-icon', 'image_query' => 'road works'],
            ]]);
        $this->assertSame('sites/1/s.jpg', $services['shared']['items'][0]['image'], 'photo kept by index');
        $this->assertSame('crane', $services['shared']['items'][0]['icon'], 'icon comes from the model');
        $this->assertSame('sparkle', $services['shared']['items'][1]['icon'], 'unknown icon → default');
        $this->assertSame(['excavator', 'road works'], $services['ai']['item_queries']);
    }

    #[Test]
    public function image_fill_covers_new_slots_only_when_empty(): void
    {
        $site = $this->site();
        $page = $site->pages()->first();
        $mine = 'sites/'.$site->id.'/mine.jpg';
        $services = Section::factory()->for($page)->create(['type' => SectionType::Services, 'sort' => 10, 'content' => ['ar' => ['title' => 'قديم']]]);
        $stats = Section::factory()->for($page)->create(['type' => SectionType::Stats, 'sort' => 11, 'content' => ['ar' => ['title' => 'قديم'], 'shared' => ['image' => $mine]]]);

        $this->reply = function () use ($site, $services, $stats) {
            $reply = $this->replyFor($site->fresh());
            foreach ($reply['sections'] as &$entry) {
                if ($entry['id'] === $services->id) {
                    $entry['content'] = ['ar' => ['title' => 'خدمات', 'intro' => 'م'], 'en' => ['title' => 'Services', 'intro' => 'i'], 'items' => [
                        ['title_ar' => 'حفر', 'title_en' => 'Excavation', 'description_ar' => 'و', 'description_en' => 'd', 'icon' => 'crane', 'image_query' => 'excavator'],
                    ]];
                }
                if ($entry['id'] === $stats->id) {
                    $entry['content'] = ['ar' => ['title' => 'أرقام'], 'en' => ['title' => 'Figures'], 'items' => [], 'image_query' => 'construction site aerial'];
                }
                if ($entry['type'] === 'cta') {
                    $entry['content']['image_query'] = 'engineers handshake';
                }
            }

            return $reply;
        };
        $this->fakePixabay();

        $run = $this->runNow($site, null, ['fill_images' => true]);

        $this->assertSame(AiRun::DONE, $run->status, (string) $run->error);
        $this->assertStringStartsWith("sites/{$site->id}/media/pixabay-", $services->fresh()->content['shared']['items'][0]['image']);
        $this->assertStringStartsWith("sites/{$site->id}/media/pixabay-", $this->sectionOf($site, SectionType::Cta)->content['shared']['image']);
        $this->assertStringStartsWith("sites/{$site->id}/media/pixabay-", $this->sectionOf($site, SectionType::About)->content['shared']['image2']);
        $this->assertSame($mine, $stats->fresh()->content['shared']['image'], 'hand-picked background kept');
    }

    #[Test]
    public function ai_errors_fail_the_run_with_the_message(): void
    {
        $site = $this->site();
        $this->reply = fn () => new AiException('خدمة Claude مشغولة الآن، حاول مرة أخرى بعد قليل.');

        $run = $this->runNow($site);

        $this->assertSame(AiRun::FAILED, $run->status);
        $this->assertSame('خدمة Claude مشغولة الآن، حاول مرة أخرى بعد قليل.', $run->error);
        $this->assertSame('قديم', $this->sectionOf($site, SectionType::Hero)->content['ar']['title']);
    }

    #[Test]
    public function unexpected_errors_fail_the_run_without_leaking_details(): void
    {
        $site = $this->site();
        $this->reply = fn () => new \LogicException('secret stack detail');

        $run = $this->runNow($site);

        $this->assertSame(AiRun::FAILED, $run->status);
        $this->assertStringNotContainsString('secret', $run->error);
    }

    #[Test]
    public function only_one_active_run_per_site_and_stale_runs_expire(): void
    {
        Queue::fake();
        $site = $this->site();
        $runs = app(AiRuns::class);

        $first = $runs->start($site);
        Queue::assertPushed(GenerateSiteContent::class, fn ($job) => $job->runId === $first->id);

        try {
            $runs->start($site);
            $this->fail('second run should be refused');
        } catch (AiException $e) {
            $this->assertStringContainsString('قيد التنفيذ', $e->getMessage());
        }

        $first->forceFill(['created_at' => now()->subMinutes(30)])->save();
        $second = $runs->start($site);

        $this->assertSame(AiRun::FAILED, $first->fresh()->status);
        $this->assertNotSame($first->id, $second->id);
    }

    #[Test]
    public function starting_without_a_key_explains_how_to_enable_it(): void
    {
        $this->aiConfigured = false;

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('AI_API_KEY');
        app(AiRuns::class)->start($this->site());
    }

    #[Test]
    public function a_killed_job_never_leaves_the_run_spinning(): void
    {
        Queue::fake();
        $run = app(AiRuns::class)->start($this->site());

        (new GenerateSiteContent($run->id))->failed(new \RuntimeException('timeout'));

        $this->assertSame(AiRun::FAILED, $run->fresh()->status);
    }

    // ----------------------------------------------------------------- admin

    private function admin(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel('admin');
    }

    #[Test]
    public function site_page_action_starts_a_whole_site_run(): void
    {
        Queue::fake();
        $this->admin();
        $site = $this->site();

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->callAction('aiGenerateSite', ['instructions' => 'رسمي'])
            ->assertHasNoActionErrors()
            ->assertDispatched('ai-run-started');

        $run = $site->aiRuns()->first();
        $this->assertNull($run->section_ids);
        $this->assertSame('رسمي', $run->options['instructions']);
        $this->assertTrue($run->options['seo']);
        Queue::assertPushed(GenerateSiteContent::class);
    }

    #[Test]
    public function generate_action_is_disabled_without_a_key(): void
    {
        $this->aiConfigured = false;
        $this->admin();
        $site = $this->site();

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])->assertActionDisabled('aiGenerateSite');
    }

    #[Test]
    public function section_row_action_regenerates_one_section(): void
    {
        Queue::fake();
        $this->admin();
        $site = $this->site();
        $page = $site->pages()->first();
        $cta = $this->sectionOf($site, SectionType::Cta);

        Livewire::test(SectionsRelationManager::class, ['ownerRecord' => $page, 'pageClass' => EditPage::class])
            ->callAction(TestAction::make('aiRegenerate')->table($cta), ['instructions' => 'أقصر'])
            ->assertHasNoActionErrors();

        $this->assertSame([$cta->id], $site->aiRuns()->first()->section_ids);
    }

    #[Test]
    public function page_header_action_regenerates_all_its_sections(): void
    {
        Queue::fake();
        $this->admin();
        $site = $this->site();
        $page = $site->pages()->first();

        Livewire::test(SectionsRelationManager::class, ['ownerRecord' => $page, 'pageClass' => EditPage::class])
            ->callAction(TestAction::make('aiGeneratePage')->table());

        $this->assertEqualsCanonicalizing($page->sections()->pluck('id')->all(), $site->aiRuns()->first()->section_ids);
    }

    #[Test]
    public function progress_widget_shows_the_active_run_and_polls(): void
    {
        Queue::fake();
        $this->admin();
        $site = $this->site();
        $run = app(AiRuns::class)->start($site);
        $run->update(['status' => AiRun::RUNNING, 'stage' => 'يكتب المحتوى بالعربية والإنجليزية…', 'progress' => 40]);

        Livewire::test(AiRunProgress::class, ['record' => $site])
            ->assertSee('جارٍ توليد محتوى الموقع')
            ->assertSee('يكتب المحتوى')
            ->assertSeeHtml('wire:poll.3s');

        $run->update(['status' => AiRun::DONE, 'progress' => 100, 'stage' => 'تم', 'finished_at' => now(), 'usage' => ['sections' => 4, 'cost_usd' => 0.19]]);

        Livewire::test(AiRunProgress::class, ['record' => $site->pages()->first()])
            ->assertSee('اكتمل التوليد')
            ->assertSee('$0.19')
            ->assertDontSeeHtml('wire:poll')
            ->call('dismiss')
            ->assertDontSee('اكتمل التوليد');
    }

    #[Test]
    public function the_run_uses_the_chosen_provider_and_gemini_gets_a_narrowed_schema(): void
    {
        $this->enableGemini();
        config(['services.gemini.free_tier' => true]);
        $site = $this->site();
        $this->reply = fn () => $this->replyFor($site);

        $run = $this->runNow($site, null, ['provider' => 'gemini']);

        $this->assertSame('gemini', $this->calls[0]['provider']);
        $variants = $this->calls[0]['schema']['properties']['sections']['items']['anyOf'];
        $this->assertEqualsCanonicalizing(['hero', 'about', 'projects', 'cta'], array_map(fn ($v) => $v['properties']['type']['const'], $variants));
        $this->assertSame('gemini', $run->usage['provider']);
        $this->assertTrue($run->usage['free']);
        $this->assertEquals(0, $run->usage['cost_usd']);

        $this->calls = [];
        $this->runNow($site, null, ['provider' => 'claude']);
        $this->assertSame('claude', $this->calls[0]['provider']);
        $this->assertCount(count(SectionType::cases()), $this->calls[0]['schema']['properties']['sections']['items']['anyOf'], 'Claude keeps the stable, cached schema');
    }

    #[Test]
    public function an_unconfigured_provider_falls_back_to_the_default(): void
    {
        Queue::fake();
        config(['services.ai.provider' => 'claude']);

        $run = app(AiRuns::class)->start($this->site(), null, ['provider' => 'gemini']); // gemini has no key here

        $this->assertSame('claude', $run->options['provider']);
    }

    #[Test]
    public function dialog_offers_a_model_choice_only_when_both_are_configured(): void
    {
        Queue::fake();
        $this->admin();
        $site = $this->site();

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->mountAction('aiGenerateSite')
            ->assertFormFieldHidden('provider');

        $this->enableGemini();

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->callAction('aiGenerateSite', ['provider' => 'gemini'])
            ->assertHasNoActionErrors();

        $this->assertSame('gemini', $site->aiRuns()->first()->options['provider']);
    }

    #[Test]
    public function wizard_can_generate_content_right_after_creating_the_site(): void
    {
        Queue::fake();
        $this->admin();
        $industry = Industry::where('key', 'contracting')->firstOrFail();

        Livewire::test(CreateSite::class)
            ->fillForm([
                'company_name_ar' => 'شركة النور', 'company_name_en' => 'Alnour', 'slug' => 'alnour',
                'industry_id' => $industry->id, 'template' => 'industrial', 'default_locale' => 'ar',
                'build_pages' => true, 'ai_generate' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $site = Site::where('slug', 'alnour')->firstOrFail();
        $this->assertTrue($site->pages()->exists());
        $this->assertSame(1, $site->aiRuns()->count());
        Queue::assertPushed(GenerateSiteContent::class);
    }
}
