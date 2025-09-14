<?php

namespace App\Repositories\Interfaces;

use App\Models\TransferListing;

interface TransferListingRepositoryInterface
{
    public function create(array $data): TransferListing;

    public function findActiveByPlayerId(int $playerId): ?TransferListing;
}
