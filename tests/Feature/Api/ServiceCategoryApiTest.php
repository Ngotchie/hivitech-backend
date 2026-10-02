<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_active_categories_and_services_in_display_order(): void
    {
        $second = ServiceCategory::factory()->create([
            'name' => 'Bêta',
            'sort_order' => 20,
            'is_active' => true,
        ]);
        $first = ServiceCategory::factory()->create([
            'name' => 'Alpha',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $inactive = ServiceCategory::factory()->create([
            'name' => 'Masquée',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        Service::factory()->for($first, 'category')->create([
            'name' => 'Service B',
            'sort_order' => 20,
            'is_active' => true,
        ]);
        Service::factory()->for($first, 'category')->create([
            'name' => 'Service A',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        Service::factory()->for($first, 'category')->create([
            'name' => 'Service masqué',
            'sort_order' => 0,
            'is_active' => false,
        ]);
        Service::factory()->for($inactive, 'category')->create(['is_active' => true]);

        $response = $this->getJson('/api/service-categories')->assertOk();

        $response->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->getKey())
            ->assertJsonPath('data.1.id', $second->getKey())
            ->assertJsonPath('data.0.services.0.name', 'Service A')
            ->assertJsonPath('data.0.services.1.name', 'Service B')
            ->assertJsonCount(2, 'data.0.services');
    }

    public function test_it_exposes_only_the_public_summary_fields(): void
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        Service::factory()->for($category, 'category')->create([
            'is_active' => true,
            'starting_price' => 150000,
            'seo_title' => 'Interne',
        ]);

        $response = $this->getJson('/api/service-categories')->assertOk();

        $response->assertJsonStructure([
            'data' => [[
                'id',
                'name',
                'slug',
                'description',
                'icon',
                'image_path',
                'services' => [[
                    'id',
                    'name',
                    'slug',
                    'short_description',
                ]],
            ]],
        ])->assertJsonMissingPath('data.0.is_active')
            ->assertJsonMissingPath('data.0.sort_order')
            ->assertJsonMissingPath('data.0.created_at')
            ->assertJsonMissingPath('data.0.services.0.starting_price')
            ->assertJsonMissingPath('data.0.services.0.seo_title');
    }
}
