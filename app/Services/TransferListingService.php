<?php

namespace App\Services;

use App\Enums\TransferStatus;
use App\Models\Player;
use App\Models\TransferListing;
use App\Repositories\Interfaces\TransferListingRepositoryInterface;
use App\Services\Interfaces\PlayerServiceInterface;
use App\Services\Interfaces\TransferListingServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class TransferListingService implements TransferListingServiceInterface
{
    public function __construct(
        private TransferListingRepositoryInterface $transferListingRepository,
        private PlayerServiceInterface $playerService
    ) {}

    public function listPlayerForTransfer(int $playerId, int $teamId, float $askingPrice): TransferListing
    {
        return DB::transaction(function () use ($playerId, $teamId, $askingPrice) {
            // Check if player exists and belongs to the team
            $player = $this->playerService->get($playerId);

            if (! $player || $player->team_id !== $teamId) {
                throw new ModelNotFoundException('Player not found or does not belong to your team.');
            }

            // Check if player is already on transfer market
            $existingListing = $this->transferListingRepository->findActiveByPlayerId($playerId);
            if ($existingListing) {
                throw new \Exception('Player is already listed for transfer.');
            }

            // Create transfer listing
            $listingData = [
                'player_id' => $playerId,
                'selling_team_id' => $teamId,
                'asking_price' => $askingPrice,
                'status' => TransferStatus::ACTIVE,
                'unique_key' => TransferStatus::ACTIVE->value,
            ];

            return $this->transferListingRepository->create($listingData);
        });
    }

    public function getPaginatedTransferListings(): LengthAwarePaginator
    {
        return $this->transferListingRepository->paginate();
    }
}
