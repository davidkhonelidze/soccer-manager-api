<?php

namespace App\Repositories\Interfaces;

use App\Models\TransferListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransferListingRepositoryInterface
{
    public function create(array $data): TransferListing;

    public function findActiveByPlayerId(int $playerId): ?TransferListing;

    public function paginate(): LengthAwarePaginator;
}
