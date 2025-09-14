<?php

namespace App\Aggregates;

use App\Models\Player;
use App\Models\Team;
use App\Models\TransferListing;
use App\StorableEvents\FundsTransferred;
use App\StorableEvents\PlayerTransferCompleted;
use App\StorableEvents\PlayerTransferInitiated;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class TransferAggregate extends AggregateRoot
{
    private ?int $playerId = null;

    private ?string $fromTeamUuid = null;

    private ?string $toTeamUuid = null;

    private ?float $transferFee = null;

    private bool $transferCompleted = false;

    public function initiateTransfer(
        int $playerId,
        string $buyerTeamUuid,
        float $transferFee
    ): self {
        // Validate player exists and is available for transfer
        $player = Player::find($playerId);
        if (! $player) {
            throw new \Exception('Player not found.');
        }

        // Get selling team
        $sellingTeam = Team::find($player->team_id);
        if (! $sellingTeam) {
            throw new \Exception('Selling team not found.');
        }

        // Get buying team
        $buyingTeam = Team::findByUuid($buyerTeamUuid);
        if (! $buyingTeam) {
            throw new \Exception('Buying team not found.');
        }

        // Validate player is on transfer market
        // Note: At this point, the transfer listing should already be marked as 'processing'
        // by the service layer to prevent race conditions
        $transferListing = TransferListing::where('player_id', $playerId)
            ->whereIn('status', ['active', 'processing'])
            ->first();

        if (! $transferListing) {
            throw new \Exception('Player is not available for transfer.');
        }

        // Validate sufficient funds
        if ($buyingTeam->balance < $transferFee) {
            throw new \Exception('Insufficient funds for transfer.');
        }

        // Validate not buying own player
        if ($sellingTeam->uuid === $buyerTeamUuid) {
            throw new \Exception('Cannot purchase your own player.');
        }

        // Validate transfer fee matches asking price
        if (abs($transferFee - $transferListing->asking_price) > 0.01) {
            throw new \Exception('Transfer fee does not match asking price.');
        }

        $this->recordThat(new PlayerTransferInitiated(
            $playerId,
            $sellingTeam->uuid,
            $buyerTeamUuid,
            $transferFee
        ));

        return $this;
    }

    public function transferFunds(): self
    {
        if (! $this->playerId || ! $this->fromTeamUuid || ! $this->toTeamUuid || ! $this->transferFee) {
            throw new \Exception('Transfer not properly initiated.');
        }

        $this->recordThat(new FundsTransferred(
            $this->fromTeamUuid,
            $this->toTeamUuid,
            $this->transferFee
        ));

        return $this;
    }

    public function completeTransfer(): self
    {
        if (! $this->playerId || ! $this->toTeamUuid) {
            throw new \Exception('Transfer not properly initiated.');
        }

        $this->recordThat(new PlayerTransferCompleted(
            $this->playerId,
            $this->toTeamUuid
        ));

        return $this;
    }

    public function applyPlayerTransferInitiated(PlayerTransferInitiated $event): void
    {
        $this->playerId = $event->playerId;
        $this->fromTeamUuid = $event->fromTeamUuid;
        $this->toTeamUuid = $event->toTeamUuid;
        $this->transferFee = $event->transferFee;
    }

    public function applyFundsTransferred(FundsTransferred $event): void
    {
        // Funds transfer logic is handled by the projector
    }

    public function applyPlayerTransferCompleted(PlayerTransferCompleted $event): void
    {
        $this->transferCompleted = true;
    }
}
