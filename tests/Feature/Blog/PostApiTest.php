<?php

namespace Tests\Feature\Blog;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_paginated_filtered_and_does_not_expose_content(): void
    {
        $category = PostCategory::factory()->create(['name' => 'Cybersécurité', 'slug' => 'cybersecurite', 'is_active' => true]);
        $wanted = Post::factory()->published()->for($category, 'category')->create(['content' => '<p>Contenu lourd</p>']);
        Post::factory()->published()->create();

        $this->getJson('/api/posts?category=cybersecurite&per_page=9')
            ->assertOk()
            ->assertJsonPath('data.0.id', $wanted->id)
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.content')
            ->assertJsonFragment(['slug' => 'cybersecurite'])
            ->assertJsonPath('meta.per_page', 9);
    }

    public function test_detail_contains_related_published_posts_and_does_not_increment_views(): void
    {
        $category = PostCategory::factory()->create();
        $post = Post::factory()->published()->for($category, 'category')->create(['views_count' => 4]);
        $related = Post::factory()->published()->for($category, 'category')->create();
        Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->addDay()]);

        $this->getJson("/api/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.views', 4)
            ->assertJsonPath('data.related_posts.0.id', $related->id)
            ->assertJsonCount(1, 'data.related_posts');

        $this->assertSame(4, $post->fresh()->views_count);
    }
}
