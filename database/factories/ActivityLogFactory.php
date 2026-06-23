<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ActivityLog> */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'action' => 'contact.created',
            'description' => fake()->sentence(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Mozilla/5.0',
            'created_at' => now(),
        ];
    }
}
