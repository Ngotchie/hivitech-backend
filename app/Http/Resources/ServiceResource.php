<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
/** @mixin \App\Models\Service */
class ServiceResource extends JsonResource {
 public function toArray(Request $request): array {
  return ['id'=>$this->getKey(),'name'=>$this->name,'slug'=>$this->slug,'short_description'=>$this->short_description,'icon'=>$this->icon,'cover_image_url'=>$this->image_path ? Storage::disk('s3')->url($this->image_path) : null,'category'=>$this->whenLoaded('category',fn()=>['id'=>$this->category->getKey(),'name'=>$this->category->name,'slug'=>$this->category->slug])];
 }
}
