<?php

namespace Database\Factories;

use App\Enums\Device;
use App\Models\Site;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Visit> */
class VisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'visitor_uuid' => fake()->uuid(),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
            'device' => fake()->randomElement(Device::cases()),
            'country' => 'EG',
            'referrer' => null,
            'landing_path' => '/',
            'started_at' => now(),
            'last_ping_at' => now(),
            'ended_at' => null,
            'duration_seconds' => 0,
            'is_return' => false,
        ];
    }

    /** Open visit whose last heartbeat was $seconds ago. */
    public function stale(int $seconds = 120): static
    {
        return $this->state(fn () => [
            'started_at' => now()->subSeconds($seconds + 60),
            'last_ping_at' => now()->subSeconds($seconds),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'started_at' => now()->subMinutes(5),
            'last_ping_at' => now()->subMinutes(3),
            'ended_at' => now()->subMinutes(3),
            'duration_seconds' => 120,
        ]);
    }
}
