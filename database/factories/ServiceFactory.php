<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $name = fake()->unique()->sentence(3);

        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(14),
            'description' => fake()->paragraphs(2, true),
            'starting_price' => fake()->optional(0.65)->randomFloat(2, 10000, 2000000),
            'price_on_request' => fake()->boolean(60),
            'duration_text' => fake()->randomElement(['1 à 3 jours', '1 à 2 semaines', 'Selon le projet']),
            'is_featured' => fake()->boolean(20),
            'is_active' => fake()->boolean(90),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
