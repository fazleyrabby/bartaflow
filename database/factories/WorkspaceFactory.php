<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WorkspaceStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'owner_id'      => User::factory(),
            'name'          => $name,
            'slug'          => Str::slug($name).'-'.Str::random(4),
            'timezone'      => 'Asia/Dhaka',
            'locale'        => 'en',
            'status'        => WorkspaceStatus::Active->value,
            'settings'      => null,
            'trial_ends_at' => null,
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => WorkspaceStatus::Suspended->value]);
    }
}
