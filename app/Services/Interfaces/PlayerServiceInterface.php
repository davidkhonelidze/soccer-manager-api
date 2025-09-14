<?php

namespace App\Services\Interfaces;

use App\Models\Player;

interface PlayerServiceInterface
{
    public function createPlayers(int $team_id, string $position, int $count = 1);
}
