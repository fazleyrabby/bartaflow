<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WhatsAppAccount>
 */
class WhatsAppAccountFactory extends Factory
{
    protected $model = WhatsAppAccount::class;

    public function definition(): array
    {
        return [
            'workspace_id'        => Workspace::factory(),
            'label'               => fake()->company() . ' WhatsApp',
            'provider'            => 'cloud_api',
            'phone_number'        => '+88017' . fake()->numerify('########'),
            'phone_number_id'     => Str::random(16),
            'business_account_id' => Str::random(16),
            'access_token'        => Str::random(64),
            'status'              => AccountStatus::Pending->value,
            'is_default'          => false,
            'last_checked_at'     => null,
        ];
    }

    public function connected(): static
    {
        return $this->state([
            'status'          => AccountStatus::Connected->value,
            'last_checked_at' => now(),
        ]);
    }

    public function error(string $reason = 'Invalid access token.'): static
    {
        return $this->state([
            'status'        => AccountStatus::Error->value,
            'status_reason' => $reason,
        ]);
    }

    public function disconnected(): static
    {
        return $this->state(['status' => AccountStatus::Disconnected->value]);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
