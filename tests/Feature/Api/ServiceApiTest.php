<?php
namespace Tests\Feature\Api;
use App\Models\PortfolioProject;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ServiceApiTest extends TestCase {
 use RefreshDatabase;
 public function test_list_is_public_ordered_and_filterable(): void {
  $category=ServiceCategory::factory()->create(['slug'=>'developpement','is_active'=>true]);
  $other=ServiceCategory::factory()->create(['slug'=>'formation','is_active'=>true]);
  $visible=Service::factory()->for($category,'category')->create(['is_active'=>true,'sort_order'=>1]);
  Service::factory()->for($category,'category')->create(['is_active'=>false]);
  Service::factory()->for($other,'category')->create(['is_active'=>true]);
  $this->getJson('/api/services?category=developpement')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.id',$visible->id)->assertJsonPath('data.0.category.slug','developpement');
 }
 public function test_detail_excludes_drafts_and_unknown_slug_is_404(): void {
  $service=Service::factory()->for(ServiceCategory::factory()->state(['is_active'=>true]),'category')->create(['is_active'=>true]);
  $published=PortfolioProject::factory()->create(['is_published'=>true]); $draft=PortfolioProject::factory()->create(['is_published'=>false]);
  $service->portfolioProjects()->attach([$published->id,$draft->id]);
  $this->getJson('/api/services/'.$service->slug)->assertOk()->assertJsonCount(1,'data.related_projects')->assertJsonPath('data.related_projects.0.id',$published->id)->assertJsonMissingPath('data.is_active');
  $this->getJson('/api/services/introuvable')->assertNotFound();
 }
}
