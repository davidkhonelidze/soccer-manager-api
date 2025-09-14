<?php

namespace App\Repositories;

use App\Models\TransferListing;
use App\Repositories\Interfaces\TransferListingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function paginate(): LengthAwarePaginator
    {
        $perPage = config('soccer.pagination.transfer_listings_per_page', 15);

        return $this->model
            ->with(['player', 'sellingTeam'])
            ->where('status', 'active')
            ->whereNotNull('unique_key')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
