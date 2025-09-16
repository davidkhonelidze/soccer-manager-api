<?php

namespace App\Services;

use App\Aggregates\TransferAggregate;
use App\Enums\TransferStatus;
use App\Models\TransferListing;
use App\Services\Interfaces\PlayerServiceInterface;
use App\Services\Interfaces\TeamServiceInterface;
use App\Services\Interfaces\TransferServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferService implements TransferServiceInterface
{
    public function __construct(
        private PlayerServiceInterface $playerService,
        private TeamServiceInterface $teamService
    ) {}

    public function purchasePlayer(int $playerId, string $buyerTeamUuid): array
    {
        return DB::transaction(function () use ($playerId, $buyerTeamUuid) {
            // Lock the transfer listing row to prevent concurrent access
            $transferListing = TransferListing::where('player_id', $playerId)
                ->where('status', TransferStatus::ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $transferListing) {
                throw new \Exception('Player is not available for transfer.');
            }

            $transferFee = $transferListing->asking_price;

            $transferListing->update([
                'status' => TransferStatus::PROCESSING,
                'unique_key' => null, // Clear unique key to prevent conflicts
            ]);

            $transferUuid = Str::uuid()->toString();

            TransferAggregate::retrieve($transferUuid)
                ->initiateTransfer($playerId, $buyerTeamUuid, $transferFee)
                ->transferFunds()
                ->completeTransfer()
                ->persist();

            $player = $this->playerService->get($playerId);
            $buyingTeam = $this->teamService->findByUuid($buyerTeamUuid);
            $sellingTeam = $this->teamService->find($transferListing->selling_team_id);

            return [
                'player' => $player,
                'buying_team' => $buyingTeam,
                'selling_team' => $sellingTeam,
                'transfer_fee' => $transferFee,
                'transfer_uuid' => $transferUuid,
            ];
        });
    }
}
