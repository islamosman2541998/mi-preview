<?php

namespace Database\Factories;

use App\Enums\NavItemType;
use App\Models\NavItem;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NavItem> */
class NavItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'parent_id' => null,
            'label_ar' => 'رابط',
            'label_en' => 'Link',
            'type' => NavItemType::Page,
            'target' => 'home',
            'sort' => 0,
            'is_cta' => false,
        ];
    }
}
