<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Services\ContactMessageService;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request, ContactMessageService $service): JsonResponse
    {
        $service->create($request->validated());

        return response()->json(['message' => 'Votre message a bien été envoyé.'], 201);
    }
}
