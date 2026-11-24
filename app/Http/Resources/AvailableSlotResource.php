<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes available slot data into the stable response shape used by API clients.
 */
class AvailableSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'start_time' => $this->resource['start_time'],
            'end_time' => $this->resource['end_time'],
        ];
    }
}
