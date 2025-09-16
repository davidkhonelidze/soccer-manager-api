<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Services\Interfaces\TeamAuthorizationServiceInterface;

class TeamAuthorizationService implements TeamAuthorizationServiceInterface
{
    public function canUserUpdateTeam(int $userId, int $teamId): bool
    {
        $user = User::find($userId);

        if (! $user || ! $user->team_id) {
            return false;
        }

        // Check if the team exists
        $team = Team::find($teamId);
        if (! $team) {
            return false;
        }

        // Check if user's team_id matches the team being updated
        return $user->team_id === $teamId;
    }

    public function userHasTeam(int $userId): bool
    {
        $user = User::find($userId);

        return $user && $user->team_id !== null;
    }

    public function getUserTeamId(int $userId): ?int
    {
        $user = User::find($userId);

        return $user?->team_id;
    }

    public function teamExists(int $teamId): bool
    {
        return Team::find($teamId) !== null;
    }
}
