<?php

namespace Tests\Feature;

use App\Enums\SectionType;
use App\Models\Site;
use App\Services\SiteBuilder;
use App\Services\SiteStructurePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteStructurePlannerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function social_content_expands_a_generic_site_into_a_full_multi_page_site(): void
    {
        $posts = array_map(fn (int $i) => [
            'caption' => "Interior design project {$i} completed for a client",
            'image_url' => "https://cdninstagram.com/project-{$i}.jpg",
        ], range(1, 12));

        $site = Site::factory()->create([
            'industry_id' => null,
            'short_description' => 'Architecture, interior design and fit-out studio',
            'source_data' => ['platform' => 'instagram', 'biography' => 'Design studio', 'posts' => $posts],
        ]);

        app(SiteBuilder::class)->build($site);

        $homeTypes = $site->homePage->sections()->pluck('type')->all();
        $this->assertGreaterThanOrEqual(9, count($homeTypes));
        $this->assertContains(SectionType::Services, $homeTypes);
        $this->assertContains(SectionType::Projects, $homeTypes);
        $this->assertContains(SectionType::Gallery, $homeTypes);
        $this->assertContains(SectionType::Testimonials, $homeTypes);
        $this->assertContains(SectionType::Faq, $homeTypes);

        $this->assertEqualsCanonicalizing(
            ['home', 'contact', 'about', 'services', 'projects'],
            $site->pages()->pluck('slug')->all(),
        );
        $this->assertGreaterThanOrEqual(6, $site->navItems()->count(), 'pages plus the WhatsApp CTA appear in navigation');
    }

    #[Test]
    public function enriching_an_existing_site_is_idempotent(): void
    {
        $site = Site::factory()->create(['source_data' => ['posts' => array_fill(0, 8, ['caption' => 'مشروع تصميم', 'image_url' => 'https://cdninstagram.com/a.jpg'])]]);
        app(SiteBuilder::class)->build($site);
        $pages = $site->pages()->count();
        $sections = $site->sections()->count();

        $this->assertFalse(app(SiteStructurePlanner::class)->enrich($site));
        $this->assertSame($pages, $site->pages()->count());
        $this->assertSame($sections, $site->sections()->count());
    }
}
