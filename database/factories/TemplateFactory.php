<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TemplateCategory;
use App\Enums\TemplateStatus;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Template> */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'category' => TemplateCategory::General,
            'body' => 'Hi {{ name }}, your order {{ order_id }} is confirmed. Thanks for choosing {{ business_name }}!',
            'variables' => null,
            'language' => 'en',
            'provider_template_name' => null,
            'status' => TemplateStatus::Active,
            'created_by' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TemplateStatus::Archived,
        ]);
    }

    public function category(TemplateCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}
