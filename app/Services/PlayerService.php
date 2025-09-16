<?php

namespace App\Services;

use App\Models\Player;
use App\Repositories\Interfaces\PlayerRepositoryInterface;
use App\Services\Interfaces\PlayerServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function updatePlayer(int $playerId, int $teamId, array $data): Player
    {
        $player = $this->repository->find($playerId);

        if (! $player) {
            throw new \Exception('Player not found.');
        }

        if ($player->team_id !== $teamId) {
            throw new \Exception('You can only update players from your own team.');
        }

        $updated = $this->repository->update($playerId, $data);

        if (! $updated) {
            throw new \Exception('Failed to update player.');
        }

        return $this->repository->find($playerId);
    }

    public function getPaginatedPlayers(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }
}
