<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageStatus;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Message> */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'recipient_phone' => '+8801'.fake()->numerify('#########'),
            'recipient_name' => fake()->name(),
            'body' => fake()->sentence(),
            'variables_used' => null,
            'direction' => 'outbound',
            'status' => MessageStatus::Queued,
            'attempts' => 0,
            'idempotency_key' => (string) Str::uuid(),
            'queued_at' => now(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::Sent,
            'provider_message_id' => 'wamid.'.Str::random(16),
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::Failed,
            'error_code' => 'permanent',
            'error_message' => 'Invalid recipient.',
            'failed_at' => now(),
        ]);
    }
}
