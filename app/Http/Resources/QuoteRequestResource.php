<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'first_name' => $this->first_name,
            'service' => $this->whenLoaded('service', fn (): ?array => $this->service ? [
                'id' => $this->service->getKey(),
                'name' => $this->service->name,
            ] : null),
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
