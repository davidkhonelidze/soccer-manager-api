<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'team_id' => $this->team_id,
            'team' => $this->whenLoaded('team', function () {
                return [
                    'id' => $this->team->id,
                    'uuid' => $this->team->uuid,
                    'name' => $this->team->name,
                    'balance' => $this->team->balance,
                    'value' => (float) ($this->team->players_sum_value ?? 0),
                    'country_id' => $this->team->country_id,
                    'country' => $this->when($this->team->relationLoaded('country'), function () {
                        return [
                            'id' => $this->team->country->id,
                            'name' => $this->team->country->name,
                            'code' => $this->team->country->code,
                        ];
                    }),
                    'created_at' => $this->team->created_at,
                    'updated_at' => $this->team->updated_at,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
