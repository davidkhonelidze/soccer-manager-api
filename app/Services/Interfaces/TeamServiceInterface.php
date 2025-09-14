<?php

namespace App\Services\Interfaces;

use App\Models\Team;

interface TeamServiceInterface
{
    public function createTeam(array $data): Team;
}
