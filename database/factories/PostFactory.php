<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->sentence(7));
        $status = fake()->randomElement(PostStatus::cases());
        $content = fake()->paragraphs(6, true);

        return [
            'post_category_id' => PostCategory::factory(),
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'content' => $content,
            'status' => $status,
            'is_featured' => fake()->boolean(20),
            'published_at' => $status === PostStatus::Published ? fake()->dateTimeBetween('-2 years', 'now') : null,
            'seo_title' => fake()->optional()->sentence(6),
            'seo_description' => fake()->optional()->sentence(18),
            'reading_time' => max(1, (int) ceil(str_word_count($content) / 200)),
            'views_count' => fake()->numberBetween(0, 5000),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => PostStatus::Draft, 'published_at' => null]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }
}
