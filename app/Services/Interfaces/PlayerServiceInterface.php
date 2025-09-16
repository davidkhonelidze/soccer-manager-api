<?php

namespace App\Services\Interfaces;

use App\Models\Player;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlayerServiceInterface
{
    public function createPlayers(int $team_id, string $position, int $count = 1);

    public function get(int $id): ?Player;

    public function updatePlayer(int $playerId, int $teamId, array $data): Player;

    public function getPaginatedPlayers(array $filters = []): LengthAwarePaginator;
}
