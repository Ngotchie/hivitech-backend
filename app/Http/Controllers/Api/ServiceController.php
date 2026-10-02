<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceDetailResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
class ServiceController extends Controller {
 public function index(Request $request): AnonymousResourceCollection {
  return ServiceResource::collection(Service::query()->active()->whereHas('category', fn ($q) => $q->active())->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->string('category'))))->with('category:id,name,slug')->ordered()->get());
 }
 public function show(string $slug): ServiceDetailResource {
  $service = Service::query()->active()->where('slug', $slug)->whereHas('category', fn ($q) => $q->active())->with([
   'category' => fn ($q) => $q->select('id','name','slug')->with(['services' => fn ($s) => $s->active()->where('slug','!=',$slug)->ordered()->limit(3)]),
   'portfolioProjects' => fn ($q) => $q->published()->ordered()->limit(3)->with(['technologies' => fn ($t) => $t->active()->ordered(), 'services:id,name,slug', 'coverImage:id,portfolio_project_id,path,alt_text']),
  ])->firstOrFail();
  return new ServiceDetailResource($service);
 }
}
