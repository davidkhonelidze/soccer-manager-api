<?php

namespace App\Repositories;

use App\Models\Player;
use App\Repositories\Interfaces\PlayerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlayerRepository implements PlayerRepositoryInterface
{
    public function __construct(
        protected Player $model
    ) {}

    public function find(int $id): ?Player
    {
        return $this->model->with(['country', 'team'])->find($id);
    }

    public function update(int $id, array $data): bool
    {
        $player = $this->model->find($id);

        if (! $player) {
            return false;
        }

        return $player->update($data);
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['country', 'team']);

        foreach ($filters as $k => $v) {
            switch ($k) {
                case 'team_id':
                case 'country_id':
                    $query->where($k, $v);
                    break;
            }
        }

        return $query->paginate(config('soccer.pagination.players_per_page'));
    }
}
