<?php

namespace Tests\Feature;

use App\Enums\NavItemType;
use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Filament\Resources\Sites\Pages\CreateSite;
use App\Filament\Resources\Sites\Pages\EditSite;
use App\Filament\Resources\Sites\Pages\ListSites;
use App\Filament\Resources\Sites\RelationManagers\NavItemsRelationManager;
use App\Filament\Resources\Sites\RelationManagers\PagesRelationManager;
use App\Filament\Resources\Sites\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Sites\Resources\Pages\RelationManagers\SectionsRelationManager;
use App\Models\Industry;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitEvent;
use App\Services\SiteBuilder;
use Database\Seeders\DemoSiteSeeder;
use Database\Seeders\IndustrySeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IndustrySeeder::class);
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
        Filament::setCurrentPanel('admin');
    }

    private function demo(): Site
    {
        $this->seed(DemoSiteSeeder::class);

        return Site::where('slug', 'acme')->firstOrFail();
    }

    #[Test]
    public function every_admin_screen_renders(): void
    {
        $site = $this->demo();
        $visit = Visit::factory()->for($site)->create(['is_return' => true, 'country' => 'EG']);
        VisitEvent::factory()->for($visit)->create(['type' => 'section_view', 'payload' => ['section' => 'hero', 'seconds' => 12]]);
        VisitEvent::factory()->for($visit)->create(['type' => 'cta_click', 'payload' => ['label' => 'demo_bar']]);
        $page = $site->homePage;

        $this->get('http://app.localhost/')->assertOk()->assertSee('آخر الزيارات');
        $this->get('http://app.localhost/sites')->assertOk()->assertSee('أكمي للمقاولات العامة');
        $this->get('http://app.localhost/sites/create')->assertOk();
        $this->get("http://app.localhost/sites/{$site->id}/edit")->assertOk()->assertSee('الصفحات');
        $this->get("http://app.localhost/sites/{$site->id}/visits?visit={$visit->id}")
            ->assertOk()
            ->assertSee('وقت المشاهدة لكل قسم')
            ->assertSee('ضغط «أريد هذا الموقع»')
            ->assertSee('زيارة متكررة');
        $this->get("http://app.localhost/sites/{$site->id}/pages/{$page->id}/edit")->assertOk()->assertSee('صفحة: الرئيسية'); // relation managers load lazily
    }

    #[Test]
    public function admin_pages_ship_the_livewire_script(): void
    {
        // regression: disabling livewire.inject_assets silently broke every admin form in the browser
        auth()->logout();

        $this->get('http://app.localhost/login')->assertOk()->assertSee('livewire', false)->assertSee('<script', false);
        $this->assertNotFalse(config('livewire.inject_assets'));
    }

    #[Test]
    public function guests_are_sent_to_login(): void
    {
        auth()->logout();

        $this->get('http://app.localhost/sites')->assertRedirect('http://app.localhost/login');
    }

    #[Test]
    public function wizard_creates_a_site_with_blueprint_pages_and_nav(): void
    {
        $industry = Industry::where('key', 'restaurant')->first();

        Livewire::test(CreateSite::class)
            ->fillForm([
                'company_name_ar' => 'مطعم الريف',
                'company_name_en' => 'Al Reef Restaurant',
                'slug' => 'al-reef',
                'industry_id' => $industry->id,
                'whatsapp' => '+20 100 000 0000',
                'template' => 'industrial',
                'default_locale' => 'ar',
                'build_pages' => true,
                'socials' => ['facebook' => 'https://facebook.com/alreef', 'instagram' => null],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $site = Site::where('slug', 'al-reef')->firstOrFail();
        $this->assertSame(SiteStatus::Draft, $site->status);
        $this->assertSame(['home', 'about', 'menu', 'contact'], $site->pages->pluck('slug')->all());
        $this->assertSame(['facebook' => 'https://facebook.com/alreef'], $site->socials);
        $this->assertTrue($site->navItems()->where('is_cta', true)->where('type', NavItemType::Whatsapp)->exists());
    }

    #[Test]
    public function wizard_can_skip_building_pages(): void
    {
        Livewire::test(CreateSite::class)
            ->fillForm(['company_name_ar' => 'شركة', 'slug' => 'empty-co', 'template' => 'industrial', 'default_locale' => 'ar', 'build_pages' => false])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(0, Site::where('slug', 'empty-co')->first()->pages()->count());
    }

    #[Test]
    public function wizard_rejects_reserved_invalid_and_duplicate_slugs(): void
    {
        $this->demo();

        foreach (['clinic' => 'reserved', 'Bad_Slug' => 'format', 'acme' => 'taken'] as $slug => $why) {
            Livewire::test(CreateSite::class)
                ->fillForm(['company_name_ar' => 'شركة', 'slug' => $slug, 'template' => 'industrial', 'default_locale' => 'ar'])
                ->call('create')
                ->assertHasFormErrors(['slug']);
        }

        $this->assertSame(1, Site::count());
    }

    #[Test]
    public function copying_the_pitch_marks_a_draft_as_sent(): void
    {
        $site = $this->demo();

        Livewire::test(ListSites::class)->call('markSiteSent', $site->id);

        $site->refresh();
        $this->assertSame(SiteStatus::Sent, $site->status);
        $this->assertNotNull($site->sent_at);

        // a viewed site keeps its status
        $site->update(['status' => SiteStatus::Viewed]);
        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])->call('markSiteSent', $site->id);
        $this->assertSame(SiteStatus::Viewed, $site->fresh()->status);
    }

    #[Test]
    public function site_actions_mark_won_and_duplicate(): void
    {
        $site = $this->demo();

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->callAction('markWon')
            ->assertHasNoActionErrors();
        $this->assertSame(SiteStatus::Won, $site->fresh()->status);

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->callAction('duplicate', data: ['slug' => 'acme-copy', 'company_name_ar' => 'نسخة', 'company_name_en' => 'Copy'])
            ->assertHasNoActionErrors();

        $copy = Site::where('slug', 'acme-copy')->firstOrFail();
        $this->assertSame(SiteStatus::Draft, $copy->status);
        $this->assertSame(0, $copy->views_count);
        $this->assertSame($site->pages()->count(), $copy->pages()->count());
        $this->assertSame($site->sections()->count(), $copy->sections()->count());
        $this->assertSame($site->navItems()->count(), $copy->navItems()->count());
    }

    #[Test]
    public function duplicate_validates_the_new_slug(): void
    {
        $site = $this->demo();

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->callAction('duplicate', data: ['slug' => 'www', 'company_name_ar' => 'x'])
            ->assertHasActionErrors(['slug']);
    }

    #[Test]
    public function edit_form_saves_palette_and_settings(): void
    {
        $site = $this->demo();

        Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
            ->fillForm(['palette.primary' => '#123456', 'preloader_enabled' => false, 'status' => SiteStatus::Sent])
            ->call('save')
            ->assertHasNoFormErrors();

        $site->refresh();
        $this->assertSame('#123456', $site->palette['primary']);
        $this->assertFalse($site->preloader_enabled);
    }

    #[Test]
    public function sections_can_be_edited_with_the_per_type_form(): void
    {
        $site = $this->demo();
        $page = $site->homePage;
        $services = $page->sections()->where('type', SectionType::Services)->first();

        $component = Livewire::test(SectionsRelationManager::class, ['ownerRecord' => $page, 'pageClass' => EditPage::class]);

        $component->callAction(TestAction::make('edit')->table($services), data: [
            'c' => [
                'ar' => ['title' => 'خدماتنا الجديدة', 'intro' => null],
                'en' => ['title' => 'New services', 'intro' => null],
                'items' => [
                    ['title_ar' => 'تشطيب', 'title_en' => 'Finishing', 'description_ar' => 'وصف', 'description_en' => 'Desc', 'image' => null],
                ],
            ],
        ])->assertHasNoActionErrors();

        $services->refresh();
        $this->assertSame('خدماتنا الجديدة', $services->content['ar']['title']);
        $this->assertSame([['title' => 'Finishing', 'description' => 'Desc']], $services->content['en']['items']);
        $this->assertCount(1, $services->content['shared']['items']);
    }

    #[Test]
    public function a_new_section_starts_from_type_defaults(): void
    {
        $site = $this->demo();
        $page = $site->pages()->where('slug', 'contact')->first();

        Livewire::test(SectionsRelationManager::class, ['ownerRecord' => $page, 'pageClass' => EditPage::class])
            ->mountAction(TestAction::make('create')->table())
            ->setActionData(['type' => SectionType::Faq->value])
            ->assertActionDataSet(fn (array $data) => ($data['c']['ar']['title'] ?? null) === 'الأسئلة الشائعة')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $faq = $page->sections()->where('type', SectionType::Faq)->sole();
        $this->assertSame('الأسئلة الشائعة', $faq->content['ar']['title']);
        $this->assertSame($page->sections()->max('sort'), $faq->sort);
    }

    #[Test]
    public function pages_can_be_added_with_slug_rules(): void
    {
        $site = $this->demo();
        $rm = fn () => Livewire::test(PagesRelationManager::class, ['ownerRecord' => $site, 'pageClass' => EditSite::class]);

        $rm()->callAction(TestAction::make('create')->table(), data: ['title_ar' => 'الوظائف', 'slug' => 'careers', 'is_published' => true])
            ->assertHasNoActionErrors();
        $this->assertTrue($site->pages()->where('slug', 'careers')->exists());

        $rm()->callAction(TestAction::make('create')->table(), data: ['title_ar' => 'x', 'slug' => 'careers'])
            ->assertHasActionErrors(['slug']); // taken on this site
        $rm()->callAction(TestAction::make('create')->table(), data: ['title_ar' => 'x', 'slug' => 'en'])
            ->assertHasActionErrors(['slug']); // reserved (locale prefix)

        // same slug on another site is fine
        $other = app(SiteBuilder::class)->build(Site::factory()->create(['slug' => 'other-co']));
        Livewire::test(PagesRelationManager::class, ['ownerRecord' => $other, 'pageClass' => EditSite::class])
            ->callAction(TestAction::make('create')->table(), data: ['title_ar' => 'الوظائف', 'slug' => 'careers'])
            ->assertHasNoActionErrors();
    }

    #[Test]
    public function nav_items_support_one_dropdown_level_and_regeneration(): void
    {
        $site = $this->demo();
        $parent = $site->navItems()->where('target', 'about')->first();

        Livewire::test(NavItemsRelationManager::class, ['ownerRecord' => $site, 'pageClass' => EditSite::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'label_ar' => 'فريق العمل', 'label_en' => 'Team', 'type' => NavItemType::Anchor->value, 'target' => 'about', 'parent_id' => $parent->id,
            ])
            ->assertHasNoActionErrors();

        $child = $site->navItems()->where('label_ar', 'فريق العمل')->sole();
        $this->assertSame($parent->id, $child->parent_id);

        $this->get('http://acme.localhost/')->assertSee('فريق العمل')->assertSee('class="nav__sub"', false);

        Livewire::test(NavItemsRelationManager::class, ['ownerRecord' => $site, 'pageClass' => EditSite::class])
            ->callAction(TestAction::make('regenerate')->table())
            ->assertHasNoActionErrors();

        $this->assertFalse($site->navItems()->where('label_ar', 'فريق العمل')->exists());
    }
}
