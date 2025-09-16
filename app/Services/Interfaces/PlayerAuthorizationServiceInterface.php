<?php

namespace App\Services\Interfaces;

interface PlayerAuthorizationServiceInterface
{
    public function canUserUpdatePlayer(int $userId, int $playerId): bool;

    public function userHasTeam(int $userId): bool;

    public function playerExists(int $playerId): bool;
}
