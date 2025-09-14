<?php

namespace App\Services\Interfaces;

interface PlayerServiceInterface
{
    public function createPlayers(int $team_id, string $position, int $count = 1);
}
