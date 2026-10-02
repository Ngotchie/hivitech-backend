<?php

namespace Tests\Feature\Blog;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_author_and_post_relationships_work(): void
    {
        $category = PostCategory::factory()->create();
        $author = User::factory()->create();
        $posts = Post::factory()->count(2)->for($category, 'category')->for($author, 'author')->create();

        $this->assertCount(2, $category->posts);
        $this->assertTrue($posts->first()->category->is($category));
        $this->assertTrue($posts->first()->author->is($author));
        $this->assertTrue($author->posts->contains($posts->first()));
    }

    public function test_post_slugs_are_generated_and_unique(): void
    {
        $first = Post::factory()->create(['title' => 'Pourquoi créer un site internet', 'slug' => null]);
        $second = Post::factory()->create(['title' => 'Pourquoi créer un site internet', 'slug' => null]);

        $this->assertSame('pourquoi-creer-un-site-internet', $first->slug);
        $this->assertSame('pourquoi-creer-un-site-internet-2', $second->slug);
    }

    public function test_published_scope_excludes_drafts_and_future_publications(): void
    {
        $published = Post::factory()->published()->create(['published_at' => now()->subMinute()]);
        $draft = Post::factory()->draft()->create();
        $future = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->addDay()]);

        $posts = Post::published()->get();
        $this->assertTrue($posts->contains($published));
        $this->assertFalse($posts->contains($draft));
        $this->assertFalse($posts->contains($future));
    }

    public function test_featured_scope_works(): void
    {
        $featured = Post::factory()->featured()->create();
        $regular = Post::factory()->create(['is_featured' => false]);

        $this->assertTrue(Post::featured()->get()->contains($featured));
        $this->assertFalse(Post::featured()->get()->contains($regular));
    }

    public function test_only_currently_published_posts_are_searchable(): void
    {
        $draft = Post::factory()->draft()->make();
        $future = Post::factory()->make(['status' => PostStatus::Published, 'published_at' => now()->addDay()]);
        $published = Post::factory()->published()->make(['published_at' => now()->subMinute()]);

        $this->assertFalse($draft->shouldBeSearchable());
        $this->assertFalse($future->shouldBeSearchable());
        $this->assertTrue($published->shouldBeSearchable());
    }

    public function test_reading_time_is_recalculated_from_content(): void
    {
        $post = Post::factory()->create(['content' => implode(' ', array_fill(0, 401, 'mot')), 'reading_time' => null]);

        $this->assertSame(3, $post->reading_time);

        $post->update(['content' => 'Contenu court.']);
        $this->assertSame(1, $post->fresh()->reading_time);
    }

    public function test_published_status_without_date_is_published_immediately(): void
    {
        $post = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => null]);

        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->shouldBeSearchable());
    }

    public function test_index_api_only_returns_current_publications(): void
    {
        $published = Post::factory()->published()->create(['title' => 'Article public', 'published_at' => now()->subHour()]);
        Post::factory()->draft()->create(['title' => 'Article brouillon']);
        Post::factory()->create(['title' => 'Article programmé', 'status' => PostStatus::Published, 'published_at' => now()->addDay()]);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonFragment(['slug' => $published->slug])
            ->assertJsonMissing(['title' => 'Article brouillon'])
            ->assertJsonMissing(['title' => 'Article programmé']);
    }

    public function test_show_api_returns_a_public_post_without_incrementing_views(): void
    {
        $post = Post::factory()->published()->create(['views_count' => 4]);

        $this->getJson("/api/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $post->slug)
            ->assertJsonMissingPath('data.status')
            ->assertJsonMissingPath('data.author.email')
            ->assertJsonMissingPath('data.views_count');

        $this->assertSame(4, $post->fresh()->views_count);
    }

    public function test_show_api_returns_not_found_for_draft_or_future_post(): void
    {
        $draft = Post::factory()->draft()->create();
        $future = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->addDay()]);

        $this->getJson("/api/posts/{$draft->slug}")->assertNotFound();
        $this->getJson("/api/posts/{$future->slug}")->assertNotFound();
    }

    public function test_factories_create_valid_records_and_states(): void
    {
        $category = PostCategory::factory()->create();
        $post = Post::factory()->published()->featured()->for($category, 'category')->create();

        $this->assertDatabaseHas('post_categories', ['id' => $category->getKey()]);
        $this->assertDatabaseHas('posts', ['id' => $post->getKey(), 'status' => 'published', 'is_featured' => true]);
    }
}
