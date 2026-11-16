<?php

namespace Database\Factories;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Site> */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        $company = fake()->company();

        return [
            // "site-" prefix keeps factory slugs valid and never reserved
            'slug' => 'site-'.fake()->unique()->lexify('????????'),
            'status' => SiteStatus::Draft,
            'company_name_ar' => 'شركة '.fake()->numberBetween(100, 999),
            'company_name_en' => $company,
            'industry_id' => null,
            'template' => 'industrial',
            'short_description' => fake()->sentence(12),
            'palette' => ['primary' => '#1F3A5F', 'secondary' => '#F2A900', 'accent' => '#D9480F', 'text' => '#1B1F24', 'bg' => '#F5F5F2'],
            'phone' => '+20 2 '.fake()->numerify('#### ####'),
            'whatsapp' => '+20 10'.fake()->numerify('########'),
            'email' => fake()->companyEmail(),
            'address_ar' => 'القاهرة',
            'address_en' => 'Cairo',
            'default_locale' => 'ar',
        ];
    }

    public function status(SiteStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function archived(): static
    {
        return $this->status(SiteStatus::Archived);
    }
}
