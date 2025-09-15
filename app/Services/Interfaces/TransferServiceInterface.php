<?php

namespace App\Services\Interfaces;

interface TransferServiceInterface
{
    public function purchasePlayer(int $playerId, string $buyerTeamUuid): array;
}
