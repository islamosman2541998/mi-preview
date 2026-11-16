<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Enums\VisitEventType;
use App\Jobs\CloseStaleVisits;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use App\Services\SiteBuilder;
use App\Tracking\OwnerCookie;
use App\Tracking\VisitToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private const UUID = '7b0f7a62-3d1e-4c55-9a51-0c1d2e3f4a5b';

    private Site $site;

    /** Telegram API answer for this test (setUp fakes it once; later Http::fake calls don't override). */
    private bool $telegramUp = true;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '901743307',
        ]);

        Http::fake(['api.telegram.org/*' => fn () => $this->telegramUp
            ? Http::response(['ok' => true])
            : Http::response(['ok' => false], 500)]);

        $this->site = app(SiteBuilder::class)->build(Site::factory()->create([
            'slug' => 'acme',
            'company_name_ar' => 'أكمي للمقاولات',
            'status' => SiteStatus::Sent,
        ]));
    }

    private function track(string $endpoint, array $data = [], array $headers = [])
    {
        return $this->withHeaders(array_merge(['User-Agent' => self::BROWSER, 'CF-IPCountry' => 'EG'], $headers))
            ->postJson("http://acme.localhost/_t/{$endpoint}", $data);
    }

    private function startVisit(array $data = [], array $headers = []): string
    {
        return $this->track('start', array_merge(['uuid' => self::UUID, 'path' => '/'], $data), $headers)
            ->assertOk()
            ->assertJson(['track' => true])
            ->json('token');
    }

    private function telegramMessages(): array
    {
        return Http::recorded()
            ->map(fn ($pair) => $pair[0])
            ->filter(fn (HttpRequest $r) => str_contains($r->url(), 'api.telegram.org'))
            ->map(fn (HttpRequest $r) => $r['text'])
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------ start

    #[Test]
    public function first_open_creates_a_visit_updates_the_site_and_sends_instant_alert(): void
    {
        $token = $this->startVisit(['referrer' => 'https://web.whatsapp.com/']);

        $visit = Visit::sole();
        $this->assertSame($visit->id, VisitToken::resolve($token, $this->site)->id);
        $this->assertSame('mobile', $visit->device->value);
        $this->assertSame('EG', $visit->country);
        $this->assertFalse($visit->is_return);
        $this->assertSame(VisitEventType::PageView, $visit->events->sole()->type);

        $site = $this->site->fresh();
        $this->assertSame(SiteStatus::Viewed, $site->status);
        $this->assertSame(1, $site->views_count);
        $this->assertNotNull($site->first_viewed_at);

        $messages = $this->telegramMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('🟢', $messages[0]);
        $this->assertStringContainsString('أكمي للمقاولات', $messages[0]);
        $this->assertStringContainsString('web.whatsapp.com', $messages[0]);
        Http::assertSent(fn (HttpRequest $r) => $r['chat_id'] === '901743307' && $r['parse_mode'] === 'HTML');
    }

    #[Test]
    public function won_or_lost_status_is_not_downgraded_to_viewed(): void
    {
        $this->site->update(['status' => SiteStatus::Won]);

        $this->startVisit();

        $this->assertSame(SiteStatus::Won, $this->site->fresh()->status);
    }

    #[Test]
    public function bots_are_not_tracked(): void
    {
        foreach (['WhatsApp/2.23.20.0', 'facebookexternalhit/1.1', 'TelegramBot (like TwitterBot)', 'curl/8.4', ''] as $ua) {
            $this->track('start', ['uuid' => self::UUID], ['User-Agent' => $ua])
                ->assertOk()
                ->assertJson(['track' => false]);
        }

        $this->assertSame(0, Visit::count());
        $this->assertSame([], $this->telegramMessages());
    }

    #[Test]
    public function owner_cookie_excludes_the_browser_completely(): void
    {
        // same-origin fetch sends cookies; JSON test requests only do with credentials
        $this->withCredentials()
            ->withCookie(OwnerCookie::name(), '1')
            ->track('start', ['uuid' => self::UUID])
            ->assertOk()
            ->assertJson(['track' => false]);

        $this->assertSame(0, Visit::count());
        $this->assertSame([], $this->telegramMessages());
        $this->assertSame(0, $this->site->fresh()->views_count);
    }

    #[Test]
    public function owner_page_view_does_not_even_load_the_tracker(): void
    {
        $this->get('http://acme.localhost/')->assertSee('/js/t.js', false);
        $this->withCookie(OwnerCookie::name(), '1')->get('http://acme.localhost/')->assertDontSee('/js/t.js', false);
    }

    #[Test]
    public function notifications_can_be_disabled_per_site(): void
    {
        $this->site->update(['notify_enabled' => false]);

        $token = $this->startVisit();
        $this->track('event', ['token' => $token, 'type' => 'whatsapp'])->assertNoContent();

        $this->assertSame(1, Visit::count());
        $this->assertSame([], $this->telegramMessages());
    }

    #[Test]
    public function navigating_to_another_page_resumes_the_same_visit_silently(): void
    {
        $this->startVisit();
        $this->startVisit(['path' => '/contact']);

        $visit = Visit::sole();
        $this->assertSame(['/', '/contact'], $visit->events->pluck('payload.path')->all());
        $this->assertCount(1, $this->telegramMessages());
        $this->assertSame(1, $this->site->fresh()->views_count);
    }

    #[Test]
    public function coming_back_later_is_a_return_visit_with_a_fire_alert(): void
    {
        $this->startVisit();
        Visit::sole()->update(['started_at' => now()->subHours(3), 'last_ping_at' => now()->subHours(3), 'ended_at' => now()->subHours(3)]);

        $this->startVisit();

        $this->assertSame(2, Visit::count());
        $return = Visit::latest('id')->first();
        $this->assertTrue($return->is_return);
        $this->assertDatabaseHas('visit_alerts', ['visit_id' => $return->id, 'type' => 'return']);

        $messages = $this->telegramMessages();
        $this->assertCount(2, $messages);
        $this->assertStringContainsString('🔥', $messages[1]);
        $this->assertStringContainsString('رقم 2', $messages[1]);
    }

    #[Test]
    public function start_validates_the_visitor_id(): void
    {
        $this->track('start', ['uuid' => '<script>'])->assertUnprocessable();
        $this->track('start', [])->assertUnprocessable();
    }

    // ------------------------------------------------------------------- ping

    #[Test]
    public function ping_accumulates_active_time_and_section_dwell(): void
    {
        $token = $this->startVisit();

        $this->track('ping', ['token' => $token, 'active' => 15, 'sections' => ['hero' => 9, 'services' => 6, 'bogus' => 5], 'depth' => 50])->assertNoContent();
        $this->track('ping', ['token' => $token, 'active' => 999])->assertUnprocessable();
        $this->track('ping', ['token' => $token, 'active' => 14, 'sections' => ['services' => 14]])->assertNoContent();

        $visit = Visit::sole();
        $this->assertSame(29, $visit->duration_seconds);

        $dwell = $visit->events->where('type', VisitEventType::SectionView)
            ->groupBy('payload.section')->map->sum('payload.seconds')->all();
        $this->assertSame(['hero' => 9, 'services' => 20], $dwell);
        $this->assertSame(1, $visit->events->where('type', VisitEventType::ScrollDepth)->count());
    }

    #[Test]
    public function forged_or_foreign_tokens_are_rejected(): void
    {
        $token = $this->startVisit();
        [$id] = explode('.', $token);

        $this->track('ping', ['token' => $id.'.'.str_repeat('a', 32), 'active' => 5])->assertStatus(410);
        $this->track('ping', ['token' => 'garbage', 'active' => 5])->assertStatus(410);

        // a valid token from another site's visit
        $other = Site::factory()->create(['slug' => 'other-co']);
        $foreign = VisitToken::make(Visit::factory()->for($other)->create());
        $this->track('ping', ['token' => $foreign, 'active' => 5])->assertStatus(410);

        $this->assertSame(0, Visit::where('site_id', $this->site->id)->sole()->duration_seconds);
    }

    #[Test]
    public function ping_on_a_closed_visit_asks_the_client_to_restart(): void
    {
        $token = $this->startVisit();
        Visit::sole()->update(['ended_at' => now()]);

        $this->track('ping', ['token' => $token, 'active' => 5])->assertStatus(410);
    }

    #[Test]
    public function end_beacon_flushes_without_closing_the_visit(): void
    {
        $token = $this->startVisit();

        // sendBeacon: no Accept header → still a JSON/204 response, never a redirect
        $this->withHeaders(['User-Agent' => self::BROWSER])
            ->post('http://acme.localhost/_t/end', ['token' => $token, 'active' => 7, 'sections' => ['about' => 7]])
            ->assertNoContent();

        $visit = Visit::sole();
        $this->assertSame(7, $visit->duration_seconds);
        $this->assertNull($visit->ended_at);
    }

    // ----------------------------------------------------------------- events

    #[Test]
    public function whatsapp_click_sends_one_fire_alert_per_visit(): void
    {
        $token = $this->startVisit();

        $this->track('event', ['token' => $token, 'type' => 'whatsapp', 'label' => 'hero'])->assertNoContent();
        $this->track('event', ['token' => $token, 'type' => 'whatsapp', 'label' => 'float'])->assertNoContent();
        $this->track('event', ['token' => $token, 'type' => 'phone', 'label' => 'utility'])->assertNoContent();

        $visit = Visit::sole();
        $this->assertSame(2, $visit->events->where('type', VisitEventType::WhatsappClick)->count());

        $messages = $this->telegramMessages();
        $this->assertCount(3, $messages); // instant + whatsapp + phone
        $this->assertStringContainsString('🔥', $messages[1]);
        $this->assertStringContainsString('واتساب', $messages[1]);
        $this->assertStringContainsString('الاتصال', $messages[2]);
    }

    #[Test]
    public function demo_bar_click_is_its_own_alert(): void
    {
        $token = $this->startVisit();

        $this->track('event', ['token' => $token, 'type' => 'cta', 'label' => 'nav'])->assertNoContent();
        $this->track('event', ['token' => $token, 'type' => 'cta', 'label' => 'demo_bar'])->assertNoContent();
        $this->track('event', ['token' => $token, 'type' => 'cta', 'label' => 'demo_bar'])->assertNoContent();

        $messages = $this->telegramMessages();
        $this->assertCount(3, $messages);
        $this->assertStringContainsString('أريد هذا الموقع', $messages[2]);
    }

    #[Test]
    public function event_type_is_validated(): void
    {
        $token = $this->startVisit();

        $this->track('event', ['token' => $token, 'type' => 'hack'])->assertUnprocessable();
    }

    // ------------------------------------------------------------ stale visits

    #[Test]
    public function stale_visits_are_closed_and_summarised_exactly_once(): void
    {
        $token = $this->startVisit();
        $this->startVisit(['path' => '/contact']);
        $this->track('ping', ['token' => $token, 'active' => 15, 'sections' => ['contact' => 12, 'map' => 3]]);
        $this->track('event', ['token' => $token, 'type' => 'whatsapp', 'label' => 'contact']);

        $this->travel(2)->minutes();

        $fresh = Visit::factory()->for($this->site)->create(['started_at' => now(), 'last_ping_at' => now()]);

        (new CloseStaleVisits)->handle();
        (new CloseStaleVisits)->handle();

        $closed = Visit::where('id', '!=', $fresh->id)->sole();
        $this->assertNotNull($closed->ended_at);
        $this->assertNotNull($closed->summary_sent_at);
        $this->assertNull($fresh->fresh()->ended_at);

        $summaries = array_values(array_filter($this->telegramMessages(), fn ($m) => str_contains($m, '📊')));
        $this->assertCount(1, $summaries);
        $this->assertStringContainsString('15 ث', $summaries[0]);
        $this->assertStringContainsString('الرئيسية ← تواصل معنا', $summaries[0]);
        $this->assertStringContainsString('1. التواصل — 12 ث', $summaries[0]);
        $this->assertStringContainsString('واتساب: ✅', $summaries[0]);
    }

    #[Test]
    public function quick_bounce_without_pings_uses_wall_clock_duration(): void
    {
        $visit = Visit::factory()->for($this->site)->create([
            'started_at' => now()->subMinutes(3),
            'last_ping_at' => now()->subMinutes(3)->addSeconds(8),
        ]);

        (new CloseStaleVisits)->handle();

        $this->assertSame(8, $visit->fresh()->duration_seconds);
    }

    #[Test]
    public function a_visit_reopened_after_closing_gets_a_new_summary(): void
    {
        $this->startVisit();
        $this->travel(2)->minutes();
        (new CloseStaleVisits)->handle();

        $this->travel(5)->minutes();
        $this->startVisit(['path' => '/contact']); // back within 30 min → same visit, reopened
        $this->assertSame(1, Visit::count());
        $this->assertNull(Visit::sole()->ended_at);

        $this->travel(2)->minutes();
        (new CloseStaleVisits)->handle();

        $summaries = array_filter($this->telegramMessages(), fn ($m) => str_contains($m, '📊'));
        $this->assertCount(2, $summaries);
        $this->assertCount(1, array_filter($this->telegramMessages(), fn ($m) => str_contains($m, '🟢')));
    }

    #[Test]
    public function telegram_failure_falls_back_to_the_queue_without_breaking_tracking(): void
    {
        $this->telegramUp = false;

        $this->startVisit();

        $this->assertSame(1, Visit::count());
        // sync test queue: the retry runs inline, fails again, and is logged instead of crashing the request
        Http::assertSentCount(2);
    }

    // ------------------------------------------------------------------- owner

    #[Test]
    public function open_as_owner_sets_parent_domain_cookie_then_host_cookie(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get("http://app.localhost/owner/{$this->site->id}");

        $response->assertRedirectContains('http://acme.localhost/_t/owner');
        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === OwnerCookie::name());
        $this->assertSame('.localhost', $cookie->getDomain());
        $this->assertTrue($cookie->isHttpOnly());

        $second = $this->get($response->headers->get('Location'));
        $second->assertRedirect('/');
        $hostCookie = collect($second->headers->getCookies())->first(fn ($c) => $c->getName() === OwnerCookie::name());
        $this->assertNotNull($hostCookie);
        $this->assertNull($hostCookie->getDomain());
    }

    #[Test]
    public function open_as_owner_requires_login_and_a_valid_signature(): void
    {
        $this->get("http://app.localhost/owner/{$this->site->id}")->assertRedirect('http://app.localhost/login');

        $this->get('http://acme.localhost/_t/owner')->assertForbidden();

        $expired = URL::temporarySignedRoute('preview.owner', now()->subMinute(), ['subdomain' => 'acme']);
        $this->get($expired)->assertForbidden();
    }

    #[Test]
    public function tracking_endpoints_do_not_exist_outside_preview_hosts(): void
    {
        $this->postJson('http://app.localhost/_t/start', ['uuid' => self::UUID])->assertNotFound();
        $this->postJson('http://ghost.localhost/_t/start', ['uuid' => self::UUID])->assertNotFound();
        $this->assertSame(0, Visit::count());
    }
}
