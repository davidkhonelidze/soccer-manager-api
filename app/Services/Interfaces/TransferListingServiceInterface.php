<?php

namespace App\Services\Interfaces;

use App\Models\TransferListing;

interface TransferListingServiceInterface
{
    public function listPlayerForTransfer(int $playerId, int $teamId, float $askingPrice): TransferListing;
}
