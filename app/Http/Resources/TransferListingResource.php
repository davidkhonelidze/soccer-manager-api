<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferListingResource extends JsonResource
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
            'player_id' => $this->player_id,
            'selling_team_id' => $this->selling_team_id,
            'asking_price' => $this->asking_price,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'player' => $this->whenLoaded('player', function () {
                return [
                    'id' => $this->player->id,
                    'name' => $this->player->name,
                    'position' => $this->player->position,
                    'age' => $this->player->age,
                ];
            }),
            'selling_team' => $this->whenLoaded('sellingTeam', function () {
                return [
                    'id' => $this->sellingTeam->id,
                    'name' => $this->sellingTeam->name,
                ];
            }),
        ];
    }
}
