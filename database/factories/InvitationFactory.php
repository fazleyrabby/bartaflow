<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'invited_by'   => User::factory(),
            'email'        => fake()->unique()->safeEmail(),
            'role'         => Role::Staff->value,
            'token'        => Str::random(64),
            'status'       => InvitationStatus::Pending->value,
            'expires_at'   => now()->addDays(7),
            'accepted_at'  => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status'      => InvitationStatus::Accepted->value,
            'accepted_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(['status' => InvitationStatus::Revoked->value]);
    }
}
