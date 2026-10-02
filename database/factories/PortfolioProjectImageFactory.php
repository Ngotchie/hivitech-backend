<?php

namespace Database\Factories;

use App\Models\PortfolioProject;
use App\Models\PortfolioProjectImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortfolioProjectImage> */
class PortfolioProjectImageFactory extends Factory
{
    protected $model = PortfolioProjectImage::class;

    public function definition(): array
    {
        return [
            'portfolio_project_id' => PortfolioProject::factory(),
            'path' => 'portfolio/gallery/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(5),
            'caption' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_cover' => false,
        ];
    }
}
