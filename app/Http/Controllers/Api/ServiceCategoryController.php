<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = ServiceCategory::query()
            ->active()
            ->ordered()
            ->with([
                'services' => fn ($query) => $query->active()->ordered(),
            ])
            ->get();

        return ServiceCategoryResource::collection($categories);
    }
}
