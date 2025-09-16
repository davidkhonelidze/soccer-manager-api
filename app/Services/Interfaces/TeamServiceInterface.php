<?php

namespace App\Services\Interfaces;

use App\Models\Team;

interface TeamServiceInterface
{
    public function createTeam(array $data): Team;

    public function populateTeamWithPlayers(int $team_id): void;

    public function find(int $id): ?Team;

    public function findByUuid(string $uuid): ?Team;

    public function updateTeam(int $teamId, int $userId, array $data): Team;
}
