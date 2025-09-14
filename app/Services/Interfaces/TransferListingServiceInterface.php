<?php

namespace App\Services\Interfaces;

use App\Models\TransferListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransferListingServiceInterface
{
    public function listPlayerForTransfer(int $playerId, int $teamId, float $askingPrice): TransferListing;

    public function getPaginatedTransferListings(): LengthAwarePaginator;
}
