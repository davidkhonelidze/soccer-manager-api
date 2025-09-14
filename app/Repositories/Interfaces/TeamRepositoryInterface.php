<?php

namespace App\Repositories\Interfaces;

use App\Models\Team;

interface TeamRepositoryInterface
{
    public function create(array $data): Team;
}
