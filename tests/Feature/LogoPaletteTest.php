<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Services\LogoPalette;
use App\Support\Palette;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoPaletteTest extends TestCase
{
    use RefreshDatabase;

    private const FALLBACK = ['primary' => '#123456', 'secondary' => '#E8A317', 'accent' => '#C0392B'];

    /** Draws a logo with GD and returns its path. */
    private function logo(callable $draw, bool $transparent = false): string
    {
        $im = imagecreatetruecolor(400, 200);
        imagesavealpha($im, true);

        if ($transparent) {
            imagealphablending($im, false);
            imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
            imagealphablending($im, true);
        } else {
            imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        }

        $draw($im);
        $path = tempnam(sys_get_temp_dir(), 'logo').'.png';
        imagepng($im, $path);

        return $path;
    }

    private function assertReadable(array $palette): void
    {
        $this->assertGreaterThanOrEqual(4.5, Palette::bestTextContrast($palette['primary']), 'text on primary must meet AA');
        $this->assertGreaterThanOrEqual(4.5, Palette::bestTextContrast($palette['secondary']), 'button label must meet AA');
        $this->assertGreaterThanOrEqual(4.5, Palette::contrast($palette['text'], $palette['bg']), 'body text must meet AA');
    }

    #[Test]
    public function two_colour_logo_gives_primary_and_secondary_from_the_logo(): void
    {
        $path = $this->logo(function ($im) {
            imagefilledrectangle($im, 20, 20, 220, 180, imagecolorallocate($im, 0x1D, 0x4E, 0x89)); // navy
            imagefilledrectangle($im, 240, 60, 380, 140, imagecolorallocate($im, 0xF2, 0x8C, 0x28)); // orange
        });

        $result = app(LogoPalette::class)->fromFile($path, self::FALLBACK);

        $this->assertFalse($result['monochrome']);
        [$h] = Palette::hsl($result['palette']['primary']);
        $this->assertEqualsWithDelta(212, $h, 8, 'primary is the navy');
        [$h] = Palette::hsl($result['palette']['secondary']);
        $this->assertEqualsWithDelta(30, $h, 8, 'secondary is the orange');
        $this->assertSame(self::FALLBACK['accent'], $result['palette']['accent'], 'no third colour → industry accent');
        $this->assertReadable($result['palette']);
    }

    #[Test]
    public function monochrome_logo_falls_back_to_the_industry_palette(): void
    {
        $path = $this->logo(fn ($im) => imagefilledrectangle($im, 20, 60, 380, 140, imagecolorallocate($im, 12, 12, 12)));

        $result = app(LogoPalette::class)->fromFile($path, self::FALLBACK);

        $this->assertTrue($result['monochrome']);
        $this->assertSame('#123456', $result['palette']['primary']);
        $this->assertSame('#E8A317', $result['palette']['secondary']);
        $this->assertSame(Palette::DEFAULTS['bg'], $result['palette']['bg']);
    }

    #[Test]
    public function light_brand_colour_becomes_the_button_colour_with_a_dark_primary(): void
    {
        $path = $this->logo(fn ($im) => imagefilledrectangle($im, 20, 20, 380, 180, imagecolorallocate($im, 0xFF, 0xD2, 0x3F)));

        $result = app(LogoPalette::class)->fromFile($path, self::FALLBACK);

        $this->assertSame('#FDD03E', $result['palette']['secondary'], 'yellow kept for buttons');
        $this->assertGreaterThanOrEqual(4.5, Palette::contrast($result['palette']['primary'], '#FFFFFF'), 'white text on primary');
        $this->assertReadable($result['palette']);
    }

    #[Test]
    public function transparent_pixels_are_ignored(): void
    {
        $path = $this->logo(fn ($im) => imagefilledellipse($im, 200, 100, 150, 150, imagecolorallocate($im, 0xC8, 0x10, 0x2E)), transparent: true);

        $result = app(LogoPalette::class)->fromFile($path, self::FALLBACK);

        $this->assertSame(['#C60F2D'], $result['colors']);
        $this->assertReadable($result['palette']);
    }

    #[Test]
    public function unreadable_file_throws(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bad');
        file_put_contents($path, 'not an image');

        $this->expectException(\RuntimeException::class);
        app(LogoPalette::class)->fromFile($path);
    }

    #[Test]
    public function ensure_readable_only_darkens_when_needed(): void
    {
        $this->assertSame('#1F3A5F', Palette::ensureReadable('#1F3A5F'));
        $this->assertGreaterThanOrEqual(4.5, Palette::bestTextContrast(Palette::ensureReadable('#8A8A8A')));
    }

    #[Test]
    public function saving_a_palette_stores_generated_shades(): void
    {
        $site = Site::factory()->create(['palette' => ['primary' => '#1f3a5f', 'secondary' => '#F2A900', 'accent' => 'nope']]);

        $palette = $site->fresh()->palette;

        $this->assertSame('#1F3A5F', $palette['primary'], 'normalised');
        $this->assertArrayNotHasKey('accent', $palette, 'invalid colours dropped');
        $this->assertSame('#1F3A5F', $palette['shades']['primary'][500]);
        $this->assertCount(10, $palette['shades']['secondary']);
        $this->assertSame(Palette::mix('#1F3A5F', '#FFFFFF', 0.92), $palette['shades']['primary'][50]);
    }
}
