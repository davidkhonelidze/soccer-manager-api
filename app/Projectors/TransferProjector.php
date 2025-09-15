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
        // Update player's team assignment and increase value
        $player = Player::find($event->playerId);
        if ($player) {
            $newTeam = Team::findByUuid($event->newTeamUuid);
            if ($newTeam) {
                // Calculate value increase (10-100% random increase)
                $valueIncreasePercentage = $this->calculateValueIncrease();
                $newValue = $player->value * (1 + $valueIncreasePercentage / 100);

                $player->update([
                    'team_id' => $newTeam->id,
                    'value' => $newValue,
                ]);
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

    /**
     * Calculate random value increase percentage between 10% and 100%
     */
    private function calculateValueIncrease(): float
    {
        $minIncrease = config('soccer.player.value_increase.min_percentage', 10);
        $maxIncrease = config('soccer.player.value_increase.max_percentage', 100);

        return mt_rand($minIncrease * 100, $maxIncrease * 100) / 100;
    }
}
