<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactSource;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contact> */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+8801'.fake()->numerify('#########'),
            'email' => fake()->optional()->safeEmail(),
            'custom_fields' => null,
            'notes' => fake()->optional()->sentence(),
            'is_opted_out' => false,
            'opted_out_at' => null,
            'source' => ContactSource::Manual,
        ];
    }

    public function optedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_opted_out' => true,
            'opted_out_at' => now(),
        ]);
    }
}
