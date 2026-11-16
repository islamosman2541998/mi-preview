<?php

namespace Database\Factories;

use App\Enums\VisitEventType;
use App\Models\Visit;
use App\Models\VisitEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VisitEvent> */
class VisitEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'type' => VisitEventType::PageView,
            'payload' => ['path' => '/'],
            'occurred_at' => now(),
        ];
    }
}
