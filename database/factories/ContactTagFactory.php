<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContactTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactTag> */
class ContactTagFactory extends Factory
{
    protected $model = ContactTag::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
        ];
    }
}
