<?php

namespace App\Support;

/**
 * Tile spans for photo grids so the last row is never left with a hole: four columns on wide
 * screens, two on phones. From five photos up the first one leads (2×2); the last tiles widen
 * to close the final row. Templates map the tokens to their own classes:
 *
 *   lead  2 columns × 2 rows on every screen size
 *   w2    2 columns on the 4-column grid
 *   w4    the full row on the 4-column grid
 *   m2    2 columns (the full row) on the 2-column grid
 */
class GalleryLayout
{
    /** @return list<list<string>> tokens per photo, in order */
    public static function spans(int $count): array
    {
        $count = max(0, $count);
        $lead = $count >= 5;
        $spans = array_fill(0, $count, []);

        if ($count === 0) {
            return [];
        }

        if ($lead) {
            $spans[0][] = 'lead';
        }

        $first = $lead ? 1 : 0;      // index of the first regular tile
        $rest = $count - $first;
        $last = $count - 1;

        if ($rest > 0) {
            match ($rest % 4) {
                1 => $spans[$last][] = 'w4',
                2 => [$spans[$last][] = 'w2', $spans[$last - 1][] = 'w2'],
                3 => $spans[$last][] = 'w2',
                default => null,
            };

            if ($rest % 2 === 1) {
                $spans[$last][] = 'm2';
            }
        }

        return $spans;
    }

    /** Class string for one photo: each token with the template's prefix. */
    public static function classes(array $spans, int $index, string $prefix): string
    {
        return implode(' ', array_map(fn (string $token) => $prefix.$token, $spans[$index] ?? []));
    }
}
