<?php

namespace Tests\Feature;

use App\Models\PortfolioProject;
use App\Models\PortfolioProjectImage;
use App\Models\Service;
use App\Models\Technology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_exposes_published_projects_with_public_relations(): void
    {
        Storage::fake('s3');
        $published = PortfolioProject::factory()->create(['is_published' => true, 'cover_image_path' => 'portfolio/covers/project.webp']);
        $draft = PortfolioProject::factory()->create(['is_published' => false]);
        $technology = Technology::factory()->create(['is_active' => true]);
        $service = Service::factory()->create(['is_active' => true]);
        $published->technologies()->attach($technology);
        $published->services()->attach($service);

        $this->getJson('/api/portfolio-projects')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->getKey())
            ->assertJsonPath('data.0.technologies.0.slug', $technology->slug)
            ->assertJsonPath('data.0.services.0.slug', $service->slug)
            ->assertJsonMissing(['id' => $draft->getKey()])
            ->assertJsonMissingPath('data.0.is_published');
    }

    public function test_filters_and_pagination_are_supported(): void
    {
        $service = Service::factory()->create(['is_active' => true]);
        $technology = Technology::factory()->create(['is_active' => true]);
        $matching = PortfolioProject::factory()->create(['is_published' => true]);
        $matching->services()->attach($service);
        $matching->technologies()->attach($technology);
        PortfolioProject::factory()->count(2)->create(['is_published' => true]);

        $this->getJson("/api/portfolio-projects?service={$service->slug}&technology={$technology->slug}&limit=1")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('meta.per_page', 1)->assertJsonStructure(['links', 'meta']);
    }

    public function test_featured_filter_and_limit_are_respected(): void
    {
        PortfolioProject::factory()->count(3)->create(['is_published' => true, 'is_featured' => true]);
        PortfolioProject::factory()->create(['is_published' => true, 'is_featured' => false]);
        $this->getJson('/api/portfolio-projects?featured=1&limit=2')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.per_page', 2);
    }

    public function test_detail_returns_gallery_relations_and_only_published_similar_projects(): void
    {
        Storage::fake('s3');
        $service = Service::factory()->create(['is_active' => true]);
        $technology = Technology::factory()->create(['is_active' => true]);
        $project = PortfolioProject::factory()->create(['is_published' => true, 'cover_image_path' => null]);
        $project->services()->attach($service);
        $project->technologies()->attach($technology);
        PortfolioProjectImage::factory()->for($project)->create(['is_cover' => true, 'path' => 'portfolio/cover.webp']);
        PortfolioProjectImage::factory()->for($project)->create(['is_cover' => false, 'path' => 'portfolio/gallery.webp', 'sort_order' => 2]);
        $similar = PortfolioProject::factory()->create(['is_published' => true]);
        $draft = PortfolioProject::factory()->create(['is_published' => false]);
        $similar->services()->attach($service);
        $draft->services()->attach($service);

        $this->getJson("/api/portfolio-projects/{$project->slug}")->assertOk()
            ->assertJsonPath('data.id', $project->id)->assertJsonCount(1, 'data.gallery')
            ->assertJsonPath('data.gallery.0.url', Storage::disk('s3')->url('portfolio/gallery.webp'))
            ->assertJsonPath('data.services.0.slug', $service->slug)
            ->assertJsonPath('data.technologies.0.slug', $technology->slug)
            ->assertJsonCount(1, 'data.similar_projects')
            ->assertJsonPath('data.similar_projects.0.id', $similar->id);
    }

    public function test_draft_and_unknown_detail_return_404(): void
    {
        $draft = PortfolioProject::factory()->create(['is_published' => false]);
        $this->getJson("/api/portfolio-projects/{$draft->slug}")->assertNotFound();
        $this->getJson('/api/portfolio-projects/introuvable')->assertNotFound();
    }
}
