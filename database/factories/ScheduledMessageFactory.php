<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ScheduleStatus;
use App\Models\ScheduledMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScheduledMessage> */
class ScheduledMessageFactory extends Factory
{
    protected $model = ScheduledMessage::class;

    public function definition(): array
    {
        return [
            'name' => null,
            'recipient_type' => 'contacts',
            'recipient_payload' => ['contact_ids' => []],
            'variables_override' => null,
            'run_at' => now()->addHour(),
            'timezone' => 'Asia/Dhaka',
            'recurrence' => 'none',
            'recurrence_meta' => null,
            'next_run_at' => null,
            'status' => ScheduleStatus::Pending,
            'last_error' => null,
            'processed_at' => null,
        ];
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'run_at' => now()->subMinute(),
            'status' => ScheduleStatus::Pending,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScheduleStatus::Canceled,
        ]);
    }
}
