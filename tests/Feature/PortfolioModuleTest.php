<?php

namespace Tests\Feature;

use App\Models\PortfolioProject;
use App\Models\PortfolioProjectImage;
use App\Models\Service;
use App\Models\Technology;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_technology_and_portfolio_project_have_a_many_to_many_relationship(): void
    {
        $technology = Technology::factory()->create();
        $projects = PortfolioProject::factory()->count(2)->create();
        $technology->portfolioProjects()->attach($projects);

        $this->assertCount(2, $technology->portfolioProjects);
        $this->assertTrue($projects->first()->technologies->contains($technology));
    }

    public function test_service_and_portfolio_project_have_a_many_to_many_relationship(): void
    {
        $service = Service::factory()->create();
        $projects = PortfolioProject::factory()->count(2)->create();
        $service->portfolioProjects()->attach($projects);

        $this->assertCount(2, $service->portfolioProjects);
        $this->assertTrue($projects->first()->services->contains($service));
    }

    public function test_project_images_have_the_expected_relationships(): void
    {
        $project = PortfolioProject::factory()->create();
        $images = PortfolioProjectImage::factory()->count(2)->for($project)->create();

        $this->assertCount(2, $project->images);
        $this->assertTrue($images->first()->portfolioProject->is($project));
    }

    public function test_only_one_gallery_image_remains_the_cover(): void
    {
        $project = PortfolioProject::factory()->create();
        $first = PortfolioProjectImage::factory()->for($project)->create(['is_cover' => true]);
        $second = PortfolioProjectImage::factory()->for($project)->create(['is_cover' => true]);

        $this->assertFalse($first->fresh()->is_cover);
        $this->assertTrue($second->fresh()->is_cover);
        $this->assertTrue($project->coverImage->is($second));
    }

    public function test_technology_and_project_slugs_are_generated_and_unique(): void
    {
        $firstTechnology = Technology::factory()->create(['name' => 'Vue JS', 'slug' => null]);
        $secondTechnology = Technology::factory()->create(['name' => 'Vue JS', 'slug' => null]);
        $firstProject = PortfolioProject::factory()->create(['title' => 'Portail client', 'slug' => null]);
        $secondProject = PortfolioProject::factory()->create(['title' => 'Portail client', 'slug' => null]);

        $this->assertSame('vue-js', $firstTechnology->slug);
        $this->assertSame('vue-js-2', $secondTechnology->slug);
        $this->assertSame('portail-client', $firstProject->slug);
        $this->assertSame('portail-client-2', $secondProject->slug);
    }

    public function test_published_and_featured_scopes_work(): void
    {
        $published = PortfolioProject::factory()->create(['is_published' => true, 'is_featured' => false]);
        $featured = PortfolioProject::factory()->create(['is_published' => false, 'is_featured' => true]);

        $this->assertTrue(PortfolioProject::published()->get()->contains($published));
        $this->assertFalse(PortfolioProject::published()->get()->contains($featured));
        $this->assertTrue(PortfolioProject::featured()->get()->contains($featured));
        $this->assertFalse(PortfolioProject::featured()->get()->contains($published));
    }

    public function test_technology_pivot_rejects_duplicates(): void
    {
        $project = PortfolioProject::factory()->create();
        $technology = Technology::factory()->create();
        $project->technologies()->attach($technology);

        $this->expectException(UniqueConstraintViolationException::class);

        $project->technologies()->attach($technology);
    }

    public function test_service_pivot_rejects_duplicates(): void
    {
        $project = PortfolioProject::factory()->create();
        $service = Service::factory()->create();
        $project->services()->attach($service);

        $this->expectException(UniqueConstraintViolationException::class);

        $project->services()->attach($service);
    }

    public function test_factories_create_valid_records(): void
    {
        $technology = Technology::factory()->create();
        $project = PortfolioProject::factory()->create();

        $this->assertDatabaseHas('technologies', ['id' => $technology->getKey()]);
        $this->assertDatabaseHas('portfolio_projects', ['id' => $project->getKey()]);
    }

    public function test_only_published_projects_are_searchable(): void
    {
        $draft = PortfolioProject::factory()->make(['is_published' => false]);
        $published = PortfolioProject::factory()->make(['is_published' => true]);

        $this->assertFalse($draft->shouldBeSearchable());
        $this->assertTrue($published->shouldBeSearchable());
    }
}
