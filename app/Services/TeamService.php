<?php

namespace App\Services;

use App\Models\Team;
use App\Repositories\Interfaces\TeamRepositoryInterface;
use App\Services\Interfaces\PlayerServiceInterface;
use App\Services\Interfaces\TeamServiceInterface;

class TeamService implements TeamServiceInterface
{
    public function __construct(private TeamRepositoryInterface $repository,
        private PlayerServiceInterface $playerService) {}

    public function createTeam(array $data): Team
    {
        return Team::factory()->create();
    }

    public function populateTeamWithPlayers(int $team_id)
    {
        $positions = config('soccer.team.positions');
        foreach ($positions as $position => $values) {
            $this->playerService->createPlayers($team_id, $position, $values['default_count']);
        }
    }
}
