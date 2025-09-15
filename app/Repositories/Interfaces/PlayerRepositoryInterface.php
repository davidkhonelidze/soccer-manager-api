<?php

namespace App\Repositories\Interfaces;

use App\Models\Player;

interface PlayerRepositoryInterface
{
    public function find(int $id): ?Player;

    public function update(int $id, array $data): bool;
}
