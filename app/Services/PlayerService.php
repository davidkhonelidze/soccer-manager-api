<?php

namespace App\Services;

use App\Models\Player;
use App\Repositories\Interfaces\PlayerRepositoryInterface;
use App\Services\Interfaces\PlayerServiceInterface;

class PlayerService implements PlayerServiceInterface
{
    public function __construct(private PlayerRepositoryInterface $repository) {}

    public function createPlayers(int $team_id, string $position, int $count = 1)
    {
        Player::factory($count)->create([
            'team_id' => $team_id,
            'position' => $position,
        ]);
    }

    public function get(int $id): ?Player
    {
        return $this->repository->find($id);
    }
}
