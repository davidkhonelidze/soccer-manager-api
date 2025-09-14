<?php

namespace App\Repositories;

use App\Models\TransferListing;
use App\Repositories\Interfaces\TransferListingRepositoryInterface;

class TransferListingRepository implements TransferListingRepositoryInterface
{
    public function __construct(
        protected TransferListing $model
    ) {}

    public function create(array $data): TransferListing
    {
        return $this->model->create($data);
    }

    public function findActiveByPlayerId(int $playerId): ?TransferListing
    {
        return $this->model
            ->where('player_id', $playerId)
            ->where('status', 'active')
            ->first();
    }
}
