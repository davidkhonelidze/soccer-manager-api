<?php

namespace App\Projectors;

use App\Models\Player;
use App\Models\Team;
use App\Models\TransferListing;
use App\StorableEvents\FundsTransferred;
use App\StorableEvents\PlayerTransferCompleted;
use App\StorableEvents\PlayerTransferInitiated;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class TransferProjector extends Projector
{
    public function onPlayerTransferInitiated(PlayerTransferInitiated $event): void
    {
        // This event is mainly for validation and state tracking
        // The actual changes happen in subsequent events
    }

    public function onFundsTransferred(FundsTransferred $event): void
    {
        // Decrement buyer's balance
        $buyingTeam = Team::findByUuid($event->toTeamUuid);
        if ($buyingTeam) {
            $buyingTeam->decrement('balance', $event->amount);
        }

        // Increment seller's balance
        $sellingTeam = Team::findByUuid($event->fromTeamUuid);
        if ($sellingTeam) {
            $sellingTeam->increment('balance', $event->amount);
        }
    }

    public function onPlayerTransferCompleted(PlayerTransferCompleted $event): void
    {
        // Update player's team assignment
        $player = Player::find($event->playerId);
        if ($player) {
            $newTeam = Team::findByUuid($event->newTeamUuid);
            if ($newTeam) {
                $player->update(['team_id' => $newTeam->id]);
            }
        }

        // Update transfer listing status to 'sold'
        // Handle both 'active' and 'processing' statuses for race condition safety
        TransferListing::where('player_id', $event->playerId)
            ->whereIn('status', ['active', 'processing'])
            ->update([
                'status' => 'sold',
                'unique_key' => null,
            ]);
    }
}
