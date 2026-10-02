<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_has_many_services_and_a_service_belongs_to_a_category(): void
    {
        $category = ServiceCategory::factory()->create();
        $services = Service::factory()->count(2)->for($category, 'category')->create();

        $this->assertCount(2, $category->services);
        $this->assertTrue($services->first()->category->is($category));
    }

    public function test_active_scopes_only_return_active_records(): void
    {
        $activeCategory = ServiceCategory::factory()->create(['is_active' => true]);
        $inactiveCategory = ServiceCategory::factory()->create(['is_active' => false]);
        $activeService = Service::factory()->for($activeCategory, 'category')->create(['is_active' => true]);
        $inactiveService = Service::factory()->for($inactiveCategory, 'category')->create(['is_active' => false]);

        $this->assertTrue(ServiceCategory::active()->get()->contains($activeCategory));
        $this->assertFalse(ServiceCategory::active()->get()->contains($inactiveCategory));
        $this->assertTrue(Service::active()->get()->contains($activeService));
        $this->assertFalse(Service::active()->get()->contains($inactiveService));
    }

    public function test_slugs_are_generated_and_remain_unique(): void
    {
        $first = ServiceCategory::factory()->create(['name' => 'Création de site internet', 'slug' => null]);
        $second = ServiceCategory::factory()->create(['name' => 'Création de site internet', 'slug' => null]);
        $service = Service::factory()->create(['name' => 'Audit réseau', 'slug' => null]);

        $this->assertSame('creation-de-site-internet', $first->slug);
        $this->assertSame('creation-de-site-internet-2', $second->slug);
        $this->assertSame('audit-reseau', $service->slug);
    }

    public function test_an_existing_slug_is_not_changed_when_the_name_is_edited(): void
    {
        $category = ServiceCategory::factory()->create(['name' => 'Développement', 'slug' => 'developpement-sur-mesure']);

        $category->update(['name' => 'Développement logiciel']);

        $this->assertSame('developpement-sur-mesure', $category->fresh()->slug);
    }

    public function test_a_category_with_services_cannot_be_deleted(): void
    {
        $category = ServiceCategory::factory()->create();
        Service::factory()->for($category, 'category')->create();

        $this->expectException(QueryException::class);

        $category->delete();
    }

    public function test_factories_create_valid_records(): void
    {
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->for($category, 'category')->create();

        $this->assertDatabaseHas('service_categories', ['id' => $category->getKey()]);
        $this->assertDatabaseHas('services', [
            'id' => $service->getKey(),
            'service_category_id' => $category->getKey(),
        ]);
    }
}
