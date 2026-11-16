<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'slug' => fake()->unique()->slug(2, false),
            'title_ar' => 'صفحة '.fake()->numberBetween(1, 99),
            'title_en' => ucfirst(fake()->word()),
            'is_home' => false,
            'sort' => 0,
            'is_published' => true,
        ];
    }

    public function home(): static
    {
        return $this->state(fn () => [
            'slug' => 'home',
            'title_ar' => 'الرئيسية',
            'title_en' => 'Home',
            'is_home' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
