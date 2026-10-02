<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioProjectDetailResource;
use App\Http\Resources\PortfolioProjectResource;
use App\Models\PortfolioProject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PortfolioProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = max(1, min($request->integer('limit', 12), 50));

        $projects = PortfolioProject::query()
            ->published()
            ->when($request->boolean('featured'), fn ($query) => $query->featured())
            ->when($request->filled('service'), fn ($query) => $query->whereHas(
                'services',
                fn ($services) => $services->where('slug', $request->string('service'))
            ))
            ->when($request->filled('technology'), fn ($query) => $query->whereHas(
                'technologies',
                fn ($technologies) => $technologies->where('slug', $request->string('technology'))
            ))
            ->with([
                'technologies' => fn ($query) => $query->select('technologies.id', 'name', 'slug')->active()->ordered(),
                'services' => fn ($query) => $query->select('services.id', 'name', 'slug')->active()->ordered(),
                'coverImage:id,portfolio_project_id,path,alt_text',
            ])
            ->ordered()
            ->paginate($limit);

        return PortfolioProjectResource::collection($projects);
    }

    public function show(string $slug): PortfolioProjectDetailResource
    {
        $project = PortfolioProject::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'technologies' => fn ($query) => $query->select('technologies.id', 'name', 'slug')->active()->ordered(),
                'services' => fn ($query) => $query->select('services.id', 'name', 'slug')->active()->ordered(),
                'images:id,portfolio_project_id,path,alt_text,caption,sort_order,is_cover',
                'coverImage:id,portfolio_project_id,path,alt_text',
            ])
            ->firstOrFail();

        $serviceIds = $project->services->modelKeys();
        $technologyIds = $project->technologies->modelKeys();
        if (! $serviceIds && ! $technologyIds) {
            $project->setRelation('similarProjects', collect());

            return new PortfolioProjectDetailResource($project);
        }


        $similar = PortfolioProject::query()
            ->published()
            ->whereKeyNot($project->getKey())
            ->where(function ($query) use ($serviceIds, $technologyIds): void {
                $query->when($serviceIds, fn ($projects) => $projects->whereHas(
                    'services',
                    fn ($services) => $services->whereKey($serviceIds)
                ));
                if ($technologyIds) {
                    $serviceIds
                        ? $query->orWhereHas('technologies', fn ($technologies) => $technologies->whereKey($technologyIds))
                        : $query->whereHas('technologies', fn ($technologies) => $technologies->whereKey($technologyIds));
                }
            })
            ->with([
                'technologies' => fn ($query) => $query->select('technologies.id', 'name', 'slug')->active()->ordered(),
                'services' => fn ($query) => $query->select('services.id', 'name', 'slug')->active()->ordered(),
                'coverImage:id,portfolio_project_id,path,alt_text',
            ])
            ->ordered()
            ->limit(3)
            ->get();

        $project->setRelation('similarProjects', $similar);

        return new PortfolioProjectDetailResource($project);
    }
}
