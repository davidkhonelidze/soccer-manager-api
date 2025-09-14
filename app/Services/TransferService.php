<?php

namespace App\Services;

use App\Aggregates\TransferAggregate;
use App\Models\Player;
use App\Models\Team;
use App\Models\TransferListing;
use App\Services\Interfaces\TransferServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferService implements TransferServiceInterface
{
    public function purchasePlayer(int $playerId, string $buyerTeamUuid): array
    {
        return DB::transaction(function () use ($playerId, $buyerTeamUuid) {
            // Lock the transfer listing row to prevent concurrent access
            $transferListing = TransferListing::where('player_id', $playerId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $transferListing) {
                throw new \Exception('Player is not available for transfer.');
            }

            $transferFee = $transferListing->asking_price;

            // Immediately mark the transfer listing as "processing" to prevent other requests
            // This prevents race conditions while the event sourcing process completes
            $transferListing->update([
                'status' => 'processing',
                'unique_key' => null, // Clear unique key to prevent conflicts
            ]);

            // Execute the Event Sourcing transfer within the same transaction
            // Spatie Event Sourcing handles nested transactions with savepoints
            $transferUuid = Str::uuid()->toString();

            TransferAggregate::retrieve($transferUuid)
                ->initiateTransfer($playerId, $buyerTeamUuid, $transferFee)
                ->transferFunds()
                ->completeTransfer()
                ->persist();

            // Get updated player and team information
            $player = Player::find($playerId);
            $buyingTeam = Team::findByUuid($buyerTeamUuid);
            $sellingTeam = Team::find($transferListing->selling_team_id);

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
