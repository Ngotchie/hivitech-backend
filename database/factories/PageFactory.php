<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->words(3, true));
        $status = fake()->randomElement(PageStatus::cases());

        return ['title' => $title, 'slug' => Str::slug($title), 'content' => fake()->paragraphs(4, true), 'status' => $status,
            'published_at' => $status === PageStatus::Published ? fake()->dateTimeBetween('-1 year', 'now') : null,
            'seo_title' => $title, 'seo_description' => fake()->sentence(16), 'sort_order' => fake()->numberBetween(0, 100)];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => PageStatus::Published, 'published_at' => now()->subMinute()]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => PageStatus::Draft, 'published_at' => null]);
    }
}
