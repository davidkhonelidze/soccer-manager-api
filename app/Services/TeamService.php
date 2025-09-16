<?php

namespace App\Services;

use App\Aggregates\TeamAggregate;
use App\Models\Team;
use App\Repositories\Interfaces\TeamRepositoryInterface;
use App\Services\Interfaces\PlayerServiceInterface;
use App\Services\Interfaces\TeamServiceInterface;
use Illuminate\Support\Str;

class TeamService implements TeamServiceInterface
{
    public function __construct(
        private TeamRepositoryInterface $repository,
        private PlayerServiceInterface $playerService
    ) {}

    public function createTeam(array $data): Team
    {
        $teamUuid = Str::uuid()->toString();

        $initialBalance = config('soccer.team.initial_balance');

        // Event Sourcing-ით team შექმნა
        TeamAggregate::retrieve($teamUuid)
            ->createTeam()
            ->allocateInitialFunds($initialBalance)
            ->persist();

        return Team::findByUuid($teamUuid);
    }

    public function populateTeamWithPlayers(int $team_id): void
    {
        $positions = config('soccer.team.positions');
        foreach ($positions as $position => $values) {
            $this->playerService->createPlayers($team_id, $position, $values['default_count']);
        }
    }

    public function find(int $id): ?Team
    {
        return $this->repository->find($id);
    }

    public function findByUuid(string $uuid): ?Team
    {
        return $this->repository->findByUuid($uuid);
    }

    public function updateTeam(int $teamId, int $userId, array $data): Team
    {
        $team = $this->repository->find($teamId);

        if (! $team) {
            throw new \Exception('Team not found.');
        }

        $updated = $this->repository->update($teamId, $data);

        if (! $updated) {
            throw new \Exception('Failed to update team.');
        }

        return $this->repository->find($teamId);
    }
}
