<?php

namespace App\Services\Interfaces;

interface PlayerAuthorizationServiceInterface
{
    public function canUserUpdatePlayer(int $userId, int $playerId): bool;
}
