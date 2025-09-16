<?php

namespace App\Repositories\Interfaces;

use App\Models\Team;

interface TeamRepositoryInterface
{
    public function create(array $data): Team;

    public function find(int $id): ?Team;

    public function findByUuid(string $uuid): ?Team;

    public function update(int $id, array $data): bool;
}
