<?php

namespace Database\Factories;

use App\Models\Industry;
use App\Services\SiteBuilder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Industry> */
class IndustryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2, false),
            'name_ar' => 'صناعة '.fake()->numberBetween(1, 999),
            'name_en' => ucfirst(fake()->words(2, true)),
            'sections_blueprint' => SiteBuilder::DEFAULT_BLUEPRINT,
            'stock_keywords' => fake()->words(4),
            'ai_hints' => fake()->sentence(),
            'default_palette' => [
                'primary' => fake()->hexColor(),
                'secondary' => fake()->hexColor(),
                'accent' => fake()->hexColor(),
                'text' => '#111111',
                'bg' => '#FFFFFF',
            ],
        ];
    }
}
