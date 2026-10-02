<?php

namespace Database\Factories;

use App\Models\PortfolioProject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PortfolioProject> */
class PortfolioProjectFactory extends Factory
{
    protected $model = PortfolioProject::class;

    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'client_name' => fake()->company(),
            'short_description' => fake()->sentence(14),
            'description' => fake()->paragraphs(2, true),
            'challenge' => fake()->paragraph(),
            'solution' => fake()->paragraph(),
            'results' => fake()->paragraph(),
            'website_url' => fake()->optional()->url(),
            'completed_at' => fake()->optional(0.8)->dateTimeBetween('-3 years', 'now'),
            'is_featured' => fake()->boolean(20),
            'is_published' => fake()->boolean(70),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
