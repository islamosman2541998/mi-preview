<?php

namespace Database\Factories;

use App\Enums\MediaSource;
use App\Models\Media;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Media> */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'path' => 'sites/'.fake()->uuid().'.jpg',
            'source' => MediaSource::Upload,
            'credit' => null,
            'alt_ar' => 'صورة',
            'alt_en' => 'Image',
        ];
    }
}
