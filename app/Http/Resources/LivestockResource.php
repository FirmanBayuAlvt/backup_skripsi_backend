<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LivestockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ear_tag' => $this->ear_tag,
            'breed_type' => $this->breed_type,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date->format('Y-m-d'),
            'age_days' => $this->age_days,
            'initial_weight' => (float) $this->initial_weight,
            'current_weight' => (float) $this->current_weight,
            'health_status' => $this->health_status,
            'notes' => $this->notes,
            'status' => $this->status,
            'pen' => new PenResource($this->whenLoaded('pen')),
            'weight_records' => WeightRecordResource::collection($this->whenLoaded('weightRecords')),
            'performance' => [
                'average_daily_gain' => (float) $this->average_daily_gain,
                'total_gain' => (float) ($this->current_weight - $this->initial_weight),
                'last_weight_record' => $this->weightRecords->first()?->weight_kg,
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
