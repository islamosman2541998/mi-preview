<?php

namespace Tests\Unit;

use App\Support\GalleryLayout;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GalleryLayoutTest extends TestCase
{
    /** Cells used on a grid of $cols columns: lead = 2×2, w2 = 2 columns, w4 = full row, otherwise 1. */
    private static function desktopCells(array $spans, int $cols): int
    {
        return array_sum(array_map(fn (array $s) => match (true) {
            in_array('lead', $s, true) => 4,
            in_array('w4', $s, true) => $cols,
            in_array('w2', $s, true) => 2,
            default => 1,
        }, $spans));
    }

    private static function mobileCells(array $spans): int
    {
        return array_sum(array_map(fn (array $s) => in_array('lead', $s, true) ? 4 : (in_array('m2', $s, true) ? 2 : 1), $spans));
    }

    public static function counts(): array
    {
        return array_combine(array_map(fn ($n) => "{$n} photos", range(1, 13)), array_map(fn ($n) => [$n], range(1, 13)));
    }

    #[Test]
    #[DataProvider('counts')]
    public function every_row_is_full_on_four_columns_and_on_two(int $count): void
    {
        $spans = GalleryLayout::spans($count);

        $this->assertCount($count, $spans);
        $this->assertSame(0, self::desktopCells($spans, 4) % 4, "desktop grid has a hole with {$count} photos");
        $this->assertSame(0, self::mobileCells($spans) % 2, "phone grid has a hole with {$count} photos");
    }

    #[Test]
    public function the_first_photo_leads_from_five_photos_up(): void
    {
        $this->assertContains('lead', GalleryLayout::spans(5)[0]);
        $this->assertNotContains('lead', GalleryLayout::spans(4)[0]);
    }

    #[Test]
    public function classes_prefixes_each_token(): void
    {
        $this->assertSame('sq--lead', GalleryLayout::classes(GalleryLayout::spans(5), 0, 'sq--'));
        $this->assertSame('', GalleryLayout::classes(GalleryLayout::spans(4), 0, 'sq--'));
    }
}
