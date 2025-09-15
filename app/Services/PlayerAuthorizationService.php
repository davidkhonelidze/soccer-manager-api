<?php

namespace App\Services;

use App\Repositories\Interfaces\PlayerRepositoryInterface;
use App\Services\Interfaces\PlayerAuthorizationServiceInterface;
use App\Services\Interfaces\UserServiceInterface;

class PlayerAuthorizationService implements PlayerAuthorizationServiceInterface
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private UserServiceInterface $userService
    ) {}

    public function canUserUpdatePlayer(int $userId, int $playerId): bool
    {
        $user = $this->userService->find($userId);

        if (! $user || ! $user->team_id) {
            return false;
        }

        $player = $this->playerRepository->find($playerId);

        if (! $player) {
            return false;
        }

        return $player->team_id === $user->team_id;
    }
}
