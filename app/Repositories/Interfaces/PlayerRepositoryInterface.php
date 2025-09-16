<?php

namespace App\Repositories\Interfaces;

use App\Models\Player;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlayerRepositoryInterface
{
    public function find(int $id): ?Player;

    public function update(int $id, array $data): bool;

    public function paginate(array $filters = []): LengthAwarePaginator;
}
