<?php

namespace App\Repositories;

use App\Models\Player;
use App\Repositories\Interfaces\PlayerRepositoryInterface;

class PlayerRepository implements PlayerRepositoryInterface
{
    public function __construct(
        protected Player $model
    ) {}

    public function find(int $id): ?Player
    {
        return $this->model->with('country')->find($id);
    }

    public function update(int $id, array $data): bool
    {
        $player = $this->model->find($id);

        if (! $player) {
            return false;
        }

        return $player->update($data);
    }
}
