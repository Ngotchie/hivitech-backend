<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
/** @mixin \App\Models\Service */
class ServiceDetailResource extends ServiceResource {
 public function toArray(Request $request): array {
  return [...parent::toArray($request),'description'=>$this->description,'seo'=>['title'=>$this->seo_title,'description'=>$this->seo_description],'related_projects'=>PortfolioProjectResource::collection($this->whenLoaded('portfolioProjects')),'related_services'=>ServiceResource::collection($this->relationLoaded('category') && $this->category->relationLoaded('services') ? $this->category->services : collect())];
 }
}
