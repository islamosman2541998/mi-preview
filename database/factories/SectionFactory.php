<?php

namespace Database\Factories;

use App\Enums\SectionType;
use App\Models\Page;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Section> */
class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'type' => SectionType::About,
            'variant' => null,
            'content' => [
                'ar' => ['title' => 'عنوان القسم', 'body' => 'نص تجريبي للقسم.'],
                'en' => ['title' => 'Section title', 'body' => 'Sample section text.'],
            ],
            'sort' => 0,
            'is_visible' => true,
        ];
    }

    public function type(SectionType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }
}
