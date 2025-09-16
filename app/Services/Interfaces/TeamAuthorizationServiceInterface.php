<?php

namespace App\Services\Interfaces;

interface TeamAuthorizationServiceInterface
{
    /**
     * Check if a user can update a specific team
     */
    public function canUserUpdateTeam(int $userId, int $teamId): bool;

    /**
     * Check if a team exists
     */
    public function teamExists(int $teamId): bool;

    /**
     * Check if a user has a team assigned
     */
    public function userHasTeam(int $userId): bool;

    /**
     * Get user's team ID
     */
    public function getUserTeamId(int $userId): ?int;
}
