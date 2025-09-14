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
}
