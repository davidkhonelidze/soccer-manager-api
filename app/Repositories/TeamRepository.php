<?php

namespace App\Repositories;

use App\Models\Team;
use App\Repositories\Interfaces\TeamRepositoryInterface;

class TeamRepository implements TeamRepositoryInterface
{
    public function __construct(
        protected Team $model
    ) {}

    public function create(array $data): Team
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?Team
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Team
    {
        return $this->model->findByUuid($uuid);
    }
}
