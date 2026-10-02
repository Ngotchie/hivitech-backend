<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuoteRequestRequest;
use App\Http\Resources\QuoteRequestResource;
use App\Services\QuoteRequestService;
use Illuminate\Http\JsonResponse;

class QuoteRequestController extends Controller
{
    public function store(StoreQuoteRequestRequest $request, QuoteRequestService $service): JsonResponse
    {
        $validated = $request->safe()->except('attachments');
        $quoteRequest = $service->create($validated, $request->file('attachments', []));

        return (new QuoteRequestResource($quoteRequest))
            ->response()
            ->setStatusCode(201);
    }
}
